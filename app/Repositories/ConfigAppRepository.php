<?php

namespace App\Repositories;

use App\Models\ConfigApp;
use App\Repositories\Interfaces\ConfigAppRepositoryInterface;
use Illuminate\Support\Facades\Auth;

class ConfigAppRepository implements ConfigAppRepositoryInterface
{
    // config_id fixe = 1 (ligne unique)
    private const CONFIG_ID = 'ed3acb1a-d6f6-45c0-979a-b6bbbc08cd05';

    // ─── Colonnes par section ──────────────────────────────────────────────────

    private const FIELDS_GENERAL = [
        'app_name', 'company_name', 'locale', 'timezone', 'date_format',
        'maintenance_mode', 'public_registration', 'activity_log', 'force_dark_mode',
    ];

    private const FIELDS_PROFIL = [
        'company_name', 'country', 'phone', 'email', 'website',
        'logo', 'brand_color',
        // champs non affichés dans la vue mais présents en BDD
        'rccm', 'ncc', 'sector', 'address',
    ];

    private const FIELDS_NOTIFICATIONS = [
        'email_enabled', 'mail_driver', 'mail_host', 'mail_port',
        'mail_username', 'mail_password', 'mail_from_name', 'mail_from_address',
        'sms_enabled', 'sms_provider', 'sms_api_url', 'sms_api_key', 'sms_sender',
        'notif_new_user', 'notif_new_order', 'notif_payment_ok',
        'notif_payment_fail', 'notif_low_stock', 'notif_report',
    ];

    private const FIELDS_PAIEMENTS = [
        'methods', 'online_payment_enabled', 'payment_gateway',
        'api_key_public', 'api_key_secret', 'sandbox_mode',
        'currency', 'auto_receipt', 'min_amount',
        'three_d_secure', 'anti_fraud', 'daily_limit',
    ];

    private const FIELDS_TAXES = [
        'tva_enabled', 'tva_rate', 'tva_number', 'price_display',
        'tva_on_services', 'digital_tax_rate', 'service_fee', 'tax_on_delivery',
    ];

    private const FIELDS_DEVISES = [
        'base_currency', 'currency_symbol', 'symbol_position',
        'thousands_separator', 'secondary_currencies', 'auto_rate', 'rate_frequency',
    ];

    private const FIELDS_INTEGRATIONS = [
        'google_maps_enabled', 'google_maps_key',
        'ga_tracking_id', 'fb_pixel_id',
        'livechat_enabled', 'livechat_provider', 'livechat_token',
        'social_google', 'social_facebook', 'social_github',
    ];

    private const FIELDS_SECURITE = [
        'two_factor_auth', 'two_factor_method', 'session_lifetime',
        'allow_multiple_sessions', 'password_min_length',
        'password_require_uppercase', 'password_require_number',
        'password_require_special', 'password_expiry_days',
        'max_login_attempts', 'lockout_duration', 'ip_restriction', 'allowed_ips', 'force_https',
    ];

    // ─── Méthodes publiques ────────────────────────────────────────────────────

    public function getConfig(): ?ConfigApp
    {
        return ConfigApp::with('region','ville')->find(self::CONFIG_ID);
    }

    public function updateOrCreate(array $data): ConfigApp
    {
        return ConfigApp::updateOrCreate(
            ['config_id' => self::CONFIG_ID],
            $this->withAudit($data)
        );
    }

    public function updateGeneral(array $data): ConfigApp
    {
        return $this->updateSection(self::FIELDS_GENERAL, $data);
    }

    public function updateProfil(array $data): ConfigApp
    {
        return $this->updateSection(self::FIELDS_PROFIL, $data);
    }

    public function updateNotifications(array $data): ConfigApp
    {
        return $this->updateSection(self::FIELDS_NOTIFICATIONS, $data);
    }

    public function updatePaiements(array $data): ConfigApp
    {
        //dd($data);
            return $this->updateSection(self::FIELDS_PAIEMENTS, $data);
    }

    public function updateTaxes(array $data): ConfigApp
    {
        return $this->updateSection(self::FIELDS_TAXES, $data);
    }

    public function updateDevises(array $data): ConfigApp
    {
        return $this->updateSection(self::FIELDS_DEVISES, $data);
    }

    public function updateIntegrations(array $data): ConfigApp
    {
        return $this->updateSection(self::FIELDS_INTEGRATIONS, $data);
    }

    public function updateSecurite(array $data): ConfigApp
    {
        return $this->updateSection(self::FIELDS_SECURITE, $data);
    }

    // ─── Méthodes privées ─────────────────────────────────────────────────────

    /**
     * Filtrer $data sur les colonnes autorisées pour la section,
     * puis upsert en BDD.
     */
    private function updateSection(array $allowedFields, array $data): ConfigApp
    {
        $filtered = array_intersect_key($data, array_flip($allowedFields));

        return ConfigApp::updateOrCreate(
            ['config_id' => self::CONFIG_ID],
            $this->withAudit($filtered)
        );
    }

    /**
     * Ajouter les champs d'audit (updated_by, created_by si nouveau).
     */
    private function withAudit(array $data): array
    {
        $userId = Auth::id();

        $data['updated_by'] = $userId;

        // created_by uniquement à la création (géré par updateOrCreate si absent)
        if (!ConfigApp::where('config_id', self::CONFIG_ID)->exists()) {
            $data['created_by'] = $userId;
        }

        return $data;
    }
}
