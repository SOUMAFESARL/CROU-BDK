<?php

namespace App\Services;

use App\Http\Services\Eligibilite\CriteresCampagne;
use App\Models\Campagne;
use App\Mail\AdmissionResultMail;
use App\Models\ConfigApp;
use App\Models\DemandeEtudiant;
use App\Models\Etudiant;
use App\Repositories\Interfaces\ConfigAppRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Schema;

class AdmissionResultDiffusion
{
    public const TABLE = 'admission_result_deliveries';

    public function __construct(
        private ConfigAppRepositoryInterface $config,
        private CriteresCampagne $criteres,
        private AnnonceDiffusionService $mailSettings,
    ) {}

    /**
     * Types d'envoi activés dans Paramétrage › Configurations système › Notifications.
     *
     * @return array{email: bool, sms: bool}
     */
    public static function channels(?ConfigApp $config): array
    {
        return [
            'email' => (bool) ($config?->email_enabled ?? true),
            'sms' => (bool) ($config?->sms_enabled ?? false),
        ];
    }

    /**
     * Enregistre les notifications dans la transaction de publication, avant tout envoi : une par étudiant et par type
     * d'envoi activé. Le corps est commun : mis en forme dans l'e-mail, en texte brut dans le SMS.
     *
     * @param Collection<int, Etudiant> $population
     */
    public function prepare(Campagne $campagne, Collection $population, array $classement, array $definitives, array $volets, string $liste): void
    {
        $campagneId = (string) $campagne->getKey();
        $attente = $liste === 'attente';
        $origine = $attente ? 'attente' : 'validee';
        $resultatListe = $attente ? AdmissionSmsTemplates::ATTENTE : AdmissionSmsTemplates::DEFINITIVE;
        $destinataires = [];
        foreach ($volets as $volet) {
            foreach ($definitives[$volet] ?? [] as $ligne) {
                $etudiant = $ligne['etudiant'];
                if ($ligne['commission'] === $origine && (string) $etudiant->derniereDemande?->campagne_id === $campagneId) {
                    $destinataires[(string) $etudiant->getKey()] = [$etudiant, $resultatListe];
                }
            }
        }

        $nonRetenus = $population->whereIn('id_etudiant', [...$classement['rejetees'], ...$classement['refusees']])
            ->filter(fn (Etudiant $etudiant) => (string) $etudiant->derniereDemande?->campagne_id === $campagneId);
        $voletsNonRetenus = $this->criteres->voletsDemandes($nonRetenus->pluck('derniereDemande'));
        foreach ($nonRetenus as $etudiant) {
            $id = (string) $etudiant->getKey();
            if (in_array($voletsNonRetenus[$id] ?? null, $volets, true)) {
                $destinataires[$id] ??= [$etudiant, AdmissionSmsTemplates::NON_RETENUE];
            }
        }

        $config = $this->config->getConfig();
        $settings = $config?->admission_sms_templates ?? [];
        $channels = self::channels($config);
        $messages = AdmissionSmsTemplates::resolve($settings);
        $formatted = AdmissionSmsTemplates::formatted($settings);
        $subjects = AdmissionEmailTemplates::subjects($settings);
        $date = now();
        $deliveries = [];
        foreach ($destinataires as [$etudiant, $resultat]) {
            $email = collect([$etudiant->email1, $etudiant->email2])
                ->map(fn ($address) => trim((string) $address))
                ->first(fn ($address) => filter_var($address, FILTER_VALIDATE_EMAIL));
            $phone = collect([$etudiant->telephone1, $etudiant->telephone2])
                ->map(fn ($number) => numeroCI((string) $number))->first(fn ($number) => $number !== false);
            $commun = [
                'campagne_id' => $campagneId,
                'demande_id' => (string) $etudiant->derniereDemande->getKey(),
                'resultat' => $resultat,
                'body' => $messages[$resultat],
                'created_at' => $date,
                'updated_at' => $date,
            ];
            if ($channels['email']) {
                $deliveries[] = $commun + [
                    'channel' => 'email',
                    'destination' => $email,
                    'subject' => $subjects[$resultat],
                    'body_html' => $formatted[$resultat] ?? null,
                    'status' => $email ? 'pending' : 'missing_contact',
                ];
            }
            if ($channels['sms']) {
                $deliveries[] = $commun + [
                    'channel' => 'sms',
                    'destination' => $phone,
                    'subject' => null,
                    'body_html' => null,
                    'status' => $phone ? 'pending' : 'missing_contact',
                ];
            }
        }

        foreach (array_chunk($deliveries, 200) as $batch) {
            DB::table(self::TABLE)->insertOrIgnore($batch);
        }
    }

    /** Transmet toutes les notifications en attente, par lots (après une publication) ; le planificateur reprend un envoi interrompu. */
    public function sendAll(): void
    {
        ignore_user_abort(true);
        if (PHP_SAPI !== 'cli') {
            set_time_limit(0);
        }

        while ($this->sendPending() > 0) {
            // Lot suivant.
        }
    }

    /** @return int Nombre de notifications traitées (transmises ou en échec). */
    public function sendPending(int $limit = 30): int
    {
        if (!Schema::hasTable(self::TABLE)) {
            return 0;
        }

        // Un envoi interrompu après transmission ne peut pas être confirmé sans le prestataire.
        DB::table(self::TABLE)->where('status', 'sending')->where('updated_at', '<', now()->subMinutes(10))
            ->update(['status' => 'uncertain', 'updated_at' => now()]);

        $pending = DB::table(self::TABLE)->where('status', 'pending');
        if (!(clone $pending)->exists()) {
            return 0;
        }

        $settings = ConfigApp::query()->first();
        if (!$settings?->sms_enabled || $settings->sms_provider !== 'helper_json' || !$settings->sms_api_url || !$settings->sms_api_key || !$settings->sms_sender) {
            $pending->where('channel', '!=', 'sms');
        }

        if ((clone $pending)->where('channel', 'email')->exists()) {
            try {
                $this->mailSettings->configureMail();
            } catch (\Throwable) {
                // Preserve pending mail until the application has a valid transport configuration.
                $pending->where('channel', '!=', 'email');
            }
        }

        $processed = 0;
        foreach ($pending->orderBy('id')->limit($limit)->get() as $delivery) {
            $entry = DB::table(self::TABLE)->where('id', $delivery->id);
            if (!(clone $entry)->where('status', 'pending')->update(['status' => 'sending', 'updated_at' => now()])) {
                continue;
            }

            try {
                if ($delivery->channel === 'sms') {
                    $sent = EnvoiSMS($delivery->destination, $delivery->body);
                } else {
                    $sent = Mail::to($delivery->destination)->send($this->email($delivery)) !== null;
                }
            } catch (\Throwable $exception) {
                Log::warning('Notification de résultat non transmise.', [
                    'delivery' => $delivery->id, 'channel' => $delivery->channel, 'error' => $exception->getMessage(),
                ]);
                $sent = false;
            }

            $entry->update([
                'status' => $sent ? 'sent' : 'failed',
                'sent_at' => $sent ? now() : null,
                'updated_at' => now(),
            ]);
            $processed++;
        }

        return $processed;
    }

    private function email(object $delivery): AdmissionResultMail
    {
        $demande = DemandeEtudiant::with('etudiant')->find($delivery->demande_id);
        $access = $reclamation = null;
        if ($delivery->resultat === AdmissionSmsTemplates::DEFINITIVE) {
            $student = $demande?->etudiant;
            $identifier = trim((string) $student?->email1);
            $identifier = strcasecmp($identifier, $delivery->destination) === 0 ? $identifier : null;
            $activation = $identifier && blank($student->getRawOriginal('password'));
            $forgotUrl = route('etudiant.password.request');
            $access = [
                'identifier' => $identifier,
                'loginUrl' => route('etudiant.login'),
                'forgotUrl' => $forgotUrl,
                'activation' => $activation,
                'expire' => (int) config('auth.passwords.etudiants.expire', 60),
                'passwordUrl' => $activation ? route('etudiant.password.reset', [
                    'token' => Password::broker('etudiants')->createToken($student),
                    'email' => $identifier,
                ]) : $forgotUrl,
            ];
        } elseif ($address = AdmissionEmailTemplates::reclamationEmail($this->config->getConfig())) {
            $reference = $demande?->numero_demande ?: $delivery->demande_id;
            $query = http_build_query([
                'subject' => 'Réclamation — demande '.$reference,
                'body' => "Bonjour,\n\nRéférence de la demande : ".$reference."\nMotif de la réclamation :\n\n",
            ], '', '&', PHP_QUERY_RFC3986);
            $reclamation = ['email' => $address, 'url' => 'mailto:'.$address.'?'.$query, 'reference' => $reference];
        }

        return new AdmissionResultMail($delivery->subject, $delivery->body, $delivery->body_html ?? null, $access, $reclamation);
    }
}
