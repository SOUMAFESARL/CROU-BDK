<?php

namespace App\Services;

use App\Http\Services\Eligibilite\CriteresCampagne;
use App\Models\Campagne;
use App\Models\ConfigApp;
use App\Models\Etudiant;
use App\Repositories\Interfaces\ConfigAppRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;

class AdmissionResultDiffusion
{
    public const TABLE = 'admission_result_deliveries';

    private const EMAILS = [
        AdmissionSmsTemplates::DEFINITIVE => [
            'Votre demande figure sur la liste définitive',
            'Votre demande de logement est retenue sur la liste définitive publiée.',
        ],
        AdmissionSmsTemplates::ATTENTE => [
            'Votre demande figure sur la liste d’attente définitive',
            'Votre demande de logement figure sur la liste d’attente définitive publiée.',
        ],
        AdmissionSmsTemplates::NON_RETENUE => [
            'Résultat de votre demande de logement',
            'Votre demande de logement n’a pas été retenue à l’issue de l’examen de votre dossier.',
        ],
    ];

    public function __construct(
        private ConfigAppRepositoryInterface $config,
        private CriteresCampagne $criteres,
        private AnnonceDiffusionService $mailSettings,
    ) {}

    /**
     * Enregistre les notifications dans la transaction de publication, avant tout envoi.
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

        $sms = AdmissionSmsTemplates::resolve($this->config->getConfig()?->admission_sms_templates ?? []);
        $date = now();
        $deliveries = [];
        foreach ($destinataires as [$etudiant, $resultat]) {
            $email = collect([$etudiant->email1, $etudiant->email2])
                ->map(fn ($address) => trim((string) $address))
                ->first(fn ($address) => filter_var($address, FILTER_VALIDATE_EMAIL));
            $phone = collect([$etudiant->telephone1, $etudiant->telephone2])
                ->map(fn ($number) => numeroCI((string) $number))->first(fn ($number) => $number !== false);
            [$subject, $body] = self::EMAILS[$resultat];
            $commun = [
                'campagne_id' => $campagneId,
                'demande_id' => (string) $etudiant->derniereDemande->getKey(),
                'resultat' => $resultat,
                'created_at' => $date,
                'updated_at' => $date,
            ];
            $deliveries[] = $commun + [
                'channel' => 'email',
                'destination' => $email,
                'subject' => 'CROU — '.$subject,
                'body' => "Bonjour,\n\n".$body."\n\nConsultez votre espace étudiant pour connaître les détails et la suite à donner à votre demande.\n\nLe CROU",
                'status' => $email ? 'pending' : 'missing_contact',
            ];
            $deliveries[] = $commun + [
                'channel' => 'sms',
                'destination' => $phone,
                'subject' => null,
                'body' => $sms[$resultat],
                'status' => $phone ? 'pending' : 'missing_contact',
            ];
        }

        foreach (array_chunk($deliveries, 200) as $batch) {
            DB::table(self::TABLE)->insertOrIgnore($batch);
        }
    }

    public function sendPending(int $limit = 30): void
    {
        if (!Schema::hasTable(self::TABLE)) {
            return;
        }

        // Un envoi interrompu après transmission ne peut pas être confirmé sans le prestataire.
        DB::table(self::TABLE)->where('status', 'sending')->where('updated_at', '<', now()->subMinutes(10))
            ->update(['status' => 'uncertain', 'updated_at' => now()]);

        $pending = DB::table(self::TABLE)->where('status', 'pending');
        if (!(clone $pending)->exists()) {
            return;
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

        foreach ($pending->orderBy('id')->limit($limit)->get() as $delivery) {
            $entry = DB::table(self::TABLE)->where('id', $delivery->id);
            if (!(clone $entry)->where('status', 'pending')->update(['status' => 'sending', 'updated_at' => now()])) {
                continue;
            }

            try {
                if ($delivery->channel === 'sms') {
                    $sent = EnvoiSMS($delivery->destination, $delivery->body);
                } else {
                    $sent = Mail::raw($delivery->body, fn ($message) => $message
                        ->to($delivery->destination)->subject($delivery->subject)) !== null;
                }
            } catch (\Throwable) {
                $sent = false;
            }

            $entry->update([
                'status' => $sent ? 'sent' : 'failed',
                'sent_at' => $sent ? now() : null,
                'updated_at' => now(),
            ]);
        }
    }
}
