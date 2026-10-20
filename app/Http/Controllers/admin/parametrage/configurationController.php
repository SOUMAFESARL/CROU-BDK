<?php

namespace App\Http\Controllers\admin\parametrage;

use App\Http\Controllers\Controller;
use App\Http\Requests\ConfigurationRequest;
use App\Models\ConfigApp;
use App\Repositories\Interfaces\ConfigAppRepositoryInterface;
use App\Services\AdminAccess;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class configurationController extends Controller
{
    /** Sections affichées (onglets de la page React). */
    private const SECTIONS = ['configuration', 'profil', 'notifications', 'paiements', 'taxes', 'devises', 'securite'];

    /**
     * Secrets jamais envoyés au navigateur : la page sait seulement s'ils sont renseignés (clés `smtp` / `sms`,
     * car `AdminViewData` retire des props toute clé portant le nom d'un secret).
     */
    private const SECRETS = ['smtp' => 'mail_password', 'sms' => 'sms_api_key'];

    /**
     * Séparateur de milliers échangé avec la page sous forme de clé : un espace seul serait vidé
     * par les middlewares TrimStrings / ConvertEmptyStringsToNull.
     */
    public const SEPARATEURS = ['espace' => ' ', 'point' => '.', 'virgule' => ','];

    public function __construct(
        private readonly ConfigAppRepositoryInterface $repo
    ) {}

    // ─── Affichage ────────────────────────────────────────────────────────────

    public function index(Request $request, $menus = null): Response
    {
        $menus ??= $request->query('menus');
        $section = in_array($menus, self::SECTIONS, true) ? $menus : 'configuration';
        $config = $this->repo->getConfig() ?? new ConfigApp();
        $access = app(AdminAccess::class);
        $peutModifier = $access->allows($request->user('admin'), 'admin.parametrage.configuration.update');
        $lien = fn (string $route, string $s) => route($route, $s === 'configuration' ? [] : ['menus' => $s]);

        return Inertia::render('Admin/Parametrage/Configuration', [
            'section' => $section,
            'general' => [
                'app_name' => (string) ($config->app_name ?? config('app.name')),
                'company_name' => (string) ($config->company_name ?: 'Centre Régional des Œuvres Universitaires de Bondoukou'),
                'locale' => (string) ($config->locale ?? 'fr'),
                'timezone' => (string) ($config->timezone ?? 'Africa/Abidjan'),
                'date_format' => (string) ($config->date_format ?? 'd/m/Y'),
                'maintenance_mode' => (bool) ($config->maintenance_mode ?? false),
                'activity_log' => (bool) ($config->activity_log ?? true),
            ],
            'profil' => [
                'address' => (string) $config->address,
                'country' => (string) ($config->country ?? "Côte d'Ivoire"),
                'phone' => (string) $config->phone,
                'email' => (string) $config->email,
                'website' => (string) $config->website,
                'brand_color' => systemBrandColor(),
                'logo' => $config->logo && $config->logo !== 'null' ? systemLogo() : null,
            ],
            'notifications' => [
                'email_enabled' => (bool) ($config->email_enabled ?? true),
                'mail_driver' => (string) ($config->mail_driver ?? 'smtp'),
                'mail_host' => (string) $config->mail_host,
                'mail_port' => (string) ($config->mail_port ?? 587),
                'mail_username' => (string) $config->mail_username,
                'mail_from_name' => (string) ($config->mail_from_name ?? config('app.name')),
                'mail_from_address' => (string) $config->mail_from_address,
                'sms_enabled' => (bool) ($config->sms_enabled ?? false),
                'sms_provider' => (string) ($config->sms_provider ?: 'helper_json'),
                'sms_api_url' => (string) $config->sms_api_url,
                'sms_sender' => (string) $config->sms_sender,
                ...collect(['notif_new_user', 'notif_new_order', 'notif_payment_ok', 'notif_payment_fail', 'notif_low_stock', 'notif_report'])
                    ->mapWithKeys(fn ($cle) => [$cle => (bool) ($config->{$cle} ?? true)])->all(),
            ],
            'paiements' => [
                'methods' => array_values(array_intersect(is_array($config->methods) ? $config->methods : ['cash', 'mobile'], ['cash', 'mobile', 'transfer', 'card'])),
                'online_payment_enabled' => (bool) ($config->online_payment_enabled ?? true),
                'auto_receipt' => (bool) ($config->auto_receipt ?? true),
                'min_amount' => (string) ($config->min_amount ?? 500),
                'daily_limit' => (string) ($config->daily_limit ?? 0),
            ],
            'taxes' => [
                'tva_enabled' => (bool) ($config->tva_enabled ?? false),
                'tva_rate' => (string) ($config->tva_rate ?? '18.00'),
                'tva_number' => (string) $config->tva_number,
                'price_display' => (string) ($config->price_display ?: 'TTC'),
                'tva_on_services' => (bool) ($config->tva_on_services ?? false),
                'digital_tax_rate' => (string) ($config->digital_tax_rate ?? '0.00'),
                'service_fee' => (string) ($config->service_fee ?? 0),
            ],
            'devises' => [
                'base_currency' => (string) ($config->base_currency ?: 'XOF'),
                'currency_symbol' => (string) ($config->currency_symbol ?: 'FCFA'),
                'symbol_position' => (string) ($config->symbol_position ?: 'after'),
                'thousands_separator' => (string) (array_search(systemThousandsSeparator(), self::SEPARATEURS, true) ?: 'espace'),
                'secondary_currencies' => array_values(is_array($config->secondary_currencies) ? $config->secondary_currencies : []),
                'auto_rate' => (bool) ($config->auto_rate ?? false),
                'rate_frequency' => (string) ($config->rate_frequency ?: 'daily'),
            ],
            'securite' => [
                'two_factor_auth' => (bool) ($config->two_factor_auth ?? false),
                'two_factor_method' => 'email',
                'session_lifetime' => (string) ($config->session_lifetime ?? 120),
                'allow_multiple_sessions' => (bool) ($config->allow_multiple_sessions ?? true),
                'password_min_length' => (string) ($config->password_min_length ?? 8),
                'password_require_uppercase' => (bool) ($config->password_require_uppercase ?? true),
                'password_require_number' => (bool) ($config->password_require_number ?? true),
                'password_require_special' => (bool) ($config->password_require_special ?? false),
                'max_login_attempts' => (string) ($config->max_login_attempts ?? 5),
                'lockout_duration' => (string) ($config->lockout_duration ?? 15),
                'ip_restriction' => (bool) ($config->ip_restriction ?? false),
                'allowed_ips' => (string) $config->allowed_ips,
                'force_https' => (bool) ($config->force_https ?? false),
            ],
            'contexte' => [
                // Garde-fous de la section Sécurité : ne pas se bloquer soi-même.
                'ip' => (string) $request->ip(),
                'https' => $request->secure(),
            ],
            'secrets' => array_map(fn ($colonne) => filled($config->getRawOriginal($colonne)), self::SECRETS),
            'urls' => [
                'sections' => collect(self::SECTIONS)->mapWithKeys(fn ($s) => [$s => $lien('admin.parametrage.configuration.index', $s)])->all(),
                'update' => $peutModifier
                    ? collect(self::SECTIONS)->mapWithKeys(fn ($s) => [$s => $lien('admin.parametrage.configuration.update', $s)])->all()
                    : null,
                'apiPaiement' => $access->allows($request->user('admin'), 'admin.parametrage.payment-providers.index')
                    ? route('admin.parametrage.payment-providers.index')
                    : null,
            ],
        ]);
    }

    // ─── Mise à jour ──────────────────────────────────────────────────────────

    public function update(ConfigurationRequest $request, $menus = null): RedirectResponse
    {
        $menu = in_array($menus, self::SECTIONS, true) ? $menus : 'configuration';
        $data = $request->validated();

        // Normaliser les checkboxes absents (non cochés = false/0)
        $data = $this->normalizeBooleans($data, $menu);

        // Gestion de l'upload du logo (section profil)
        // Chemin relatif à public/ (et non une URL absolue) : le logo s'affiche quel que soit le domaine (local, prod).
        if ($menu === 'profil' && $request->hasFile('logo')) {
            $fichier = $request->file('logo');
            $nom = uniqid('', true).'.'.strtolower($fichier->getClientOriginalExtension() ?: $fichier->extension());
            $fichier->move(public_path('admin/assets/images/logos'), $nom);
            $data['logo'] = 'admin/assets/images/logos/'.$nom;
        } else {
            unset($data['logo']);
        }

        // Secret laissé vide : la valeur enregistrée est conservée (elle n'est jamais renvoyée au formulaire).
        foreach (self::SECRETS as $secret) {
            if (blank($data[$secret] ?? null)) {
                unset($data[$secret]);
            }
        }

        if ($menu === 'paiements') {
            $data['methods'] = array_values(array_unique($data['methods'] ?? []));
        }
        if ($menu === 'devises') {
            $data['thousands_separator'] = self::SEPARATEURS[$data['thousands_separator'] ?? 'espace'];
            $data['secondary_currencies'] = array_values(array_diff(array_unique($data['secondary_currencies'] ?? []), [$data['base_currency']]));
        }
        if ($menu === 'securite') {
            // Seule la vérification par e-mail est disponible (AuthController).
            $data['two_factor_method'] = 'email';
        }

        // Dispatch vers la bonne méthode du repository
        match ($menu) {
            'profil'        => $this->repo->updateProfil($data),
            'notifications' => $this->repo->updateNotifications($data),
            'paiements'     => $this->repo->updatePaiements($data),
            'taxes'         => $this->repo->updateTaxes($data),
            'devises'       => $this->repo->updateDevises($data),
            'securite'      => $this->repo->updateSecurite($data),
            default         => $this->repo->updateGeneral($data),
        };

        return redirect()
            ->route('admin.parametrage.configuration.index', $menu === 'configuration' ? [] : ['menus' => $menu])
            ->with('success', 'Configuration mise à jour avec succès.');
    }

    // ─── Helpers privés ───────────────────────────────────────────────────────

    /**
     * Les checkboxes non cochées ne sont pas envoyées par le navigateur, et la page React envoie 1 / 0.
     * On les force à un booléen pour que la BDD soit correctement mise à jour.
     */
    private function normalizeBooleans(array $data, string $menu): array
    {
        $booleansByMenu = [
            'configuration' => [
                'maintenance_mode', 'activity_log',
            ],
            'notifications' => [
                'email_enabled', 'sms_enabled',
                'notif_new_user', 'notif_new_order', 'notif_payment_ok',
                'notif_payment_fail', 'notif_low_stock', 'notif_report',
            ],
            'paiements' => [
                'online_payment_enabled', 'auto_receipt',
            ],
            'taxes' => [
                'tva_enabled', 'tva_on_services',
            ],
            'devises' => [
                'auto_rate',
            ],
            'securite' => [
                'two_factor_auth', 'allow_multiple_sessions',
                'password_require_uppercase', 'password_require_number',
                'password_require_special', 'ip_restriction', 'force_https',
            ],
        ];

        foreach ($booleansByMenu[$menu] ?? [] as $field) {
            $data[$field] = filter_var($data[$field] ?? false, FILTER_VALIDATE_BOOLEAN);
        }

        return $data;
    }
}
