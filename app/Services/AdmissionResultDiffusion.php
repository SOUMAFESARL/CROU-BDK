<?php

namespace App\Services;

use App\Http\Services\Eligibilite\CriteresCampagne;
use App\Http\Services\IdentifiantsEtudiant;
use App\Models\Campagne;
use App\Mail\AdmissionResultMail;
use App\Models\ConfigApp;
use App\Models\DemandeEtudiant;
use App\Models\Etudiant;
use App\Repositories\Interfaces\ConfigAppRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Schema;

class AdmissionResultDiffusion
{
    public const TABLE = 'admission_result_deliveries';

    /** @var array<string, string> mots de passe créés pendant ce traitement, communs à l'e-mail et au SMS d'un étudiant */
    private array $motsDePasse = [];

    public function __construct(
        private ConfigAppRepositoryInterface $config,
        private CriteresCampagne $criteres,
        private AnnonceDiffusionService $mailSettings,
        private IdentifiantsEtudiant $identifiants,
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
     * d'envoi activé, sauf pour les messages désactivés (Configuration des messages). Le corps est commun : mis en forme
     * dans l'e-mail, en texte brut dans le SMS. Les informations entre accolades y sont remplies, sauf le mot de passe
     * (rempli à l'envoi pour ne pas être enregistré en clair).
     *
     * @param Collection<int, Etudiant> $population
     * @return int Nombre de notifications concernées par la publication.
     */
    public function prepare(Campagne $campagne, Collection $population, array $classement, array $definitives, array $volets, string $liste): int
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
        $enabled = AdmissionSmsTemplates::enabled($settings);
        $lien = AdmissionSmsTemplates::platformUrl($config);
        $date = now();
        $deliveries = [];
        foreach ($destinataires as [$etudiant, $resultat]) {
            if (!$enabled[$resultat]) {
                continue;
            }
            $email = collect([$etudiant->email1, $etudiant->email2])
                ->map(fn ($address) => trim((string) $address))
                ->first(fn ($address) => filter_var($address, FILTER_VALIDATE_EMAIL));
            $phone = collect([$etudiant->telephone1, $etudiant->telephone2])
                ->map(fn ($number) => numeroCI((string) $number))->first(fn ($number) => $number !== false);
            $valeurs = [
                '{{Nom_etudiant}}' => trim((string) $etudiant->name),
                '{{libelle_campagne}}' => trim((string) $campagne->libelle),
                '{{annee_campagne}}' => trim((string) $campagne->annee_campagne),
                '{{lien_plateforme}}' => $lien,
                '{{Email_etudiant}}' => trim((string) $etudiant->email1),
            ];
            $commun = [
                'campagne_id' => $campagneId,
                'demande_id' => (string) $etudiant->derniereDemande->getKey(),
                'resultat' => $resultat,
                'body' => AdmissionSmsTemplates::fill($messages[$resultat], $valeurs),
                'created_at' => $date,
                'updated_at' => $date,
            ];
            if ($channels['email']) {
                $deliveries[] = $commun + [
                    'channel' => 'email',
                    'destination' => $email,
                    'subject' => $subjects[$resultat],
                    'body_html' => isset($formatted[$resultat]) ? AdmissionSmsTemplates::fill($formatted[$resultat], $valeurs, true) : null,
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

        return count($deliveries);
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

    public function prepareDirect(DemandeEtudiant $dossier, Etudiant $student, ?string $temporaryPassword): int
    {
        $config = $this->config->getConfig();
        if (!self::channels($config)['email']) {
            return 0;
        }

        $settings = $config?->admission_sms_templates ?? [];
        $resultat = AdmissionSmsTemplates::DEFINITIVE;
        $campagne = Campagne::findOrFail($dossier->campagne_id);
        $values = [
            '{{Nom_etudiant}}' => trim((string) $student->name),
            '{{libelle_campagne}}' => trim((string) $campagne->libelle),
            '{{annee_campagne}}' => trim((string) $campagne->annee_campagne),
            '{{lien_plateforme}}' => AdmissionSmsTemplates::platformUrl($config),
            '{{Email_etudiant}}' => trim((string) $student->email1),
        ];
        $formatted = AdmissionSmsTemplates::formatted($settings)[$resultat] ?? null;
        $identity = ['demande_id' => $dossier->getKey(), 'resultat' => $resultat, 'channel' => 'email'];
        DB::table(self::TABLE)->insertOrIgnore($identity + [
            'campagne_id' => $dossier->campagne_id,
            'destination' => $student->email1,
            'temporary_password' => $temporaryPassword === null ? null : Crypt::encryptString($temporaryPassword),
            'subject' => AdmissionEmailTemplates::subjects($settings)[$resultat],
            'body' => AdmissionSmsTemplates::fill(AdmissionSmsTemplates::resolve($settings)[$resultat], $values),
            'body_html' => $formatted === null ? null : AdmissionSmsTemplates::fill($formatted, $values, true),
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return (int) DB::table(self::TABLE)->where($identity)->value('id');
    }

    /** @return int Nombre de notifications traitées (transmises ou en échec). */
    public function sendPending(int $limit = 30, ?int $deliveryId = null, array $dossierIds = []): int
    {
        if (!Schema::hasTable(self::TABLE)) {
            return 0;
        }

        // Un envoi interrompu après transmission ne peut pas être confirmé sans le prestataire.
        DB::table(self::TABLE)->where('status', 'sending')->where('updated_at', '<', now()->subMinutes(10))
            ->update(['status' => 'uncertain', 'updated_at' => now()]);

        $pending = DB::table(self::TABLE)->where('status', 'pending')
            ->when($deliveryId !== null, fn ($query) => $query->where('id', $deliveryId))
            ->when($dossierIds !== [], fn ($query) => $query
                ->whereIn('demande_id', $dossierIds)
                ->where('channel', 'email')
                ->where('resultat', AdmissionSmsTemplates::DEFINITIVE));
        if (!(clone $pending)->exists()) {
            return 0;
        }

        $settings = ConfigApp::query()->first();

        // Message désactivé après la publication : ses envois restent en attente jusqu'à sa réactivation.
        $enabled = AdmissionSmsTemplates::enabled($settings?->admission_sms_templates ?? []);
        $pending->whereNotIn('resultat', array_keys($enabled, false, true));

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
                [$etudiant, $motDePasse] = $this->remplirMotDePasse($delivery);
                if ($delivery->channel === 'sms') {
                    $sent = EnvoiSMS($delivery->destination, $delivery->body);
                } else {
                    $sent = Mail::to($delivery->destination)->send($this->email($delivery)) !== null;
                }
                // Le mot de passe n'est enregistré que s'il a été transmis.
                if ($sent && $motDePasse !== null && $this->identifiants->aEnvoyer($etudiant)) {
                    $this->identifiants->enregistrer($etudiant, $motDePasse);
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
                ...($sent && !empty($delivery->temporary_password) ? ['temporary_password' => null] : []),
                'updated_at' => now(),
            ]);
            $processed++;
        }

        return $processed;
    }

    /**
     * Remplit le mot de passe du message (en mémoire seulement) : créé pour un compte qui n'en a pas encore ; un compte
     * qui en a déjà un n'est jamais modifié, l'étudiant est invité à utiliser le sien.
     *
     * @return array{0: ?Etudiant, 1: ?string} étudiant et mot de passe communiqué
     */
    private function remplirMotDePasse(object $delivery): array
    {
        $html = $delivery->body_html ?? null;
        if (!str_contains($delivery->body.$html, AdmissionSmsTemplates::PASSWORD)) {
            return [null, null];
        }

        $etudiant = DemandeEtudiant::with('etudiant')->find($delivery->demande_id)?->etudiant;
        // Identifiants envoyés par e-mail : seulement à l'adresse de connexion de l'étudiant.
        $destinataire = $delivery->channel === 'sms' || strcasecmp(trim((string) $etudiant?->email1), $delivery->destination) === 0;
        $motDePasse = $etudiant && $destinataire
            ? $this->motDePasseAdmissionDirecte($delivery, $etudiant)
            : null;
        if ($motDePasse === null && $etudiant && $destinataire) {
            $id = (string) $etudiant->getKey();
            if ($this->identifiants->aEnvoyer($etudiant)) {
                $motDePasse = $this->motsDePasse[$id] ??= $this->identifiants->generer();
            } elseif (isset($this->motsDePasse[$id]) && Hash::check($this->motsDePasse[$id], (string) $etudiant->getRawOriginal('password'))) {
                $motDePasse = $this->motsDePasse[$id];
            }
        }

        $valeurs = [AdmissionSmsTemplates::PASSWORD => $motDePasse ?? 'votre mot de passe actuel (à défaut, « Mot de passe oublié » sur la page de connexion)'];
        $delivery->body = AdmissionSmsTemplates::fill($delivery->body, $valeurs);
        if ($html !== null) {
            $delivery->body_html = AdmissionSmsTemplates::fill($html, $valeurs, true);
        }
        $delivery->acces_dans_message = true;

        return [$etudiant, $motDePasse];
    }

    private function motDePasseAdmissionDirecte(object $delivery, Etudiant $student): ?string
    {
        if ($delivery->channel !== 'email' || empty($delivery->temporary_password)
            || strcasecmp(trim((string) $student->email1), $delivery->destination) !== 0) {
            return null;
        }

        $candidate = Crypt::decryptString($delivery->temporary_password);

        return Hash::check($candidate, (string) $student->getRawOriginal('password')) ? $candidate : null;
    }

    private function email(object $delivery): AdmissionResultMail
    {
        $demande = DemandeEtudiant::with('etudiant')->find($delivery->demande_id);
        $access = $reclamation = null;
        // Accès ajoutés seulement si le message ne les contient pas déjà (modèles antérieurs aux informations entre accolades).
        if ($delivery->resultat === AdmissionSmsTemplates::DEFINITIVE && ($delivery->acces_dans_message ?? false)) {
            $access = null;
        } elseif ($delivery->resultat === AdmissionSmsTemplates::DEFINITIVE) {
            $student = $demande?->etudiant;
            $identifier = trim((string) $student?->email1);
            $identifier = strcasecmp($identifier, $delivery->destination) === 0 ? $identifier : null;
            $activation = $identifier && blank($student->getRawOriginal('password'));
            $forgotUrl = route('etudiant.password.request');
            $access = [
                'identifier' => $identifier,
                'temporaryPassword' => $identifier ? $this->motDePasseAdmissionDirecte($delivery, $student) : null,
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
