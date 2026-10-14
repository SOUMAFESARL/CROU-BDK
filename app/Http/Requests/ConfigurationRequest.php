<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ConfigurationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return !in_array($this->route('menus') ?? $this->query('menus', 'configuration'), ['paiements', 'securite', 'taxes', 'devises'], true);
    }

    public function rules(): array
    {
        $menu = $this->route('menus') ?? $this->query('menus', 'configuration');

        return match ($menu) {
            'profil'        => $this->reglesProfil(),
            'notifications' => $this->reglesNotifications(),
            'integrations'  => $this->reglesIntegrations(),
            default         => $this->reglesGeneral(),
        };
    }

    // ─── Règles par section ────────────────────────────────────────────────────

    private function reglesGeneral(): array
    {
        return [
            'app_name'            => ['nullable', 'string', 'max:100'],
            'company_name'        => ['nullable', 'string', 'max:255'],
            'locale'              => ['nullable', 'in:fr,en,es'],
            'timezone'            => ['nullable', 'string', 'max:60'],
            'date_format'         => ['nullable', 'in:d/m/Y,m/d/Y,Y-m-d'],
            'maintenance_mode'    => ['nullable', 'boolean'],
            'activity_log'        => ['nullable', 'boolean'],
        ];
    }

    private function reglesProfil(): array
    {
        return [
            'address'      => ['nullable', 'string', 'max:255'],
            'country'      => ['nullable', 'string', 'max:80'],
            'phone'        => ['nullable', 'string', 'max:30'],
            'email'        => ['nullable', 'email', 'max:150'],
            'website'      => ['nullable', 'url', 'max:200'],
            'logo'         => ['nullable', 'image', 'mimes:png,jpg,jpeg,svg,webp', 'max:2048'],
            'brand_color'  => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
        ];
    }

    private function reglesNotifications(): array
    {
        return [
            'email_enabled'      => ['nullable', 'boolean'],
            'mail_driver'        => ['nullable', 'in:smtp,mailgun,sendgrid,ses,log'],
            'mail_host'          => ['nullable', 'string', 'max:150'],
            'mail_port'          => ['nullable', 'integer', 'min:1', 'max:65535'],
            'mail_username'      => ['nullable', 'string', 'max:150'],
            'mail_password'      => ['nullable', 'string', 'max:255'],
            'mail_from_name'     => ['nullable', 'string', 'max:100'],
            'mail_from_address'  => ['nullable', 'email', 'max:150'],
            'sms_enabled'        => ['nullable', 'boolean'],
            'sms_api_url' => ['nullable', 'url:https', 'max:2048'],
            'sms_provider'       => ['nullable', 'string', 'max:50'],
            'sms_api_key'        => ['nullable', 'string', 'max:255'],
            'sms_sender'         => ['nullable', 'string', 'max:20'],
            'notif_new_user'     => ['nullable', 'boolean'],
            'notif_new_order'    => ['nullable', 'boolean'],
            'notif_payment_ok'   => ['nullable', 'boolean'],
            'notif_payment_fail' => ['nullable', 'boolean'],
            'notif_low_stock'    => ['nullable', 'boolean'],
            'notif_report'       => ['nullable', 'boolean'],
        ];
    }

    private function reglesPaiements(): array
    {
        return [
            'payment_methods'        => ['nullable', 'array'],
            'payment_methods.*'      => ['in:card,mobile,transfer,cash,paypal,qrcode'],
            'online_payment_enabled' => ['nullable', 'boolean'],
            'payment_gateway'        => ['nullable', 'in:cinetpay,stripe,paydunya,kkiapay,flutterwave'],
            'api_key_public'         => ['nullable', 'string', 'max:255'],
            'api_key_secret'         => ['nullable', 'string', 'max:255'],
            'sandbox_mode'           => ['nullable', 'boolean'],
            'currency'               => ['nullable', 'in:XOF,EUR,USD,GHS'],
            'auto_receipt'           => ['nullable', 'boolean'],
            'min_amount'             => ['nullable', 'integer', 'min:0'],
            'three_d_secure'         => ['nullable', 'boolean'],
            'anti_fraud'             => ['nullable', 'boolean'],
            'daily_limit'            => ['nullable', 'integer', 'min:0'],
        ];
    }

    private function reglesTaxes(): array
    {
        return [
            'tva_enabled'       => ['nullable', 'boolean'],
            'tva_rate'          => ['nullable', 'numeric', 'min:0', 'max:100'],
            'tva_number'        => ['nullable', 'string', 'max:60'],
            'price_display'     => ['nullable', 'in:TTC,HT'],
            'tva_on_services'   => ['nullable', 'boolean'],
            'digital_tax_rate'  => ['nullable', 'numeric', 'min:0', 'max:100'],
            'service_fee'       => ['nullable', 'integer', 'min:0'],
            'tax_on_delivery'   => ['nullable', 'boolean'],
        ];
    }

    private function reglesDevises(): array
    {
        return [
            'base_currency'          => ['required', 'in:XOF,XAF,EUR,USD,GHS,NGN,KES'],
            'currency_symbol'        => ['nullable', 'string', 'max:10'],
            'symbol_position'        => ['nullable', 'in:before,after'],
            'thousands_separator'    => ['nullable', 'in: ,.,\,'],
            'secondary_currencies'   => ['nullable', 'array'],
            'secondary_currencies.*' => ['in:EUR,USD,GHS,NGN,XAF,GBP'],
            'auto_rate'              => ['nullable', 'boolean'],
            'rate_frequency'         => ['nullable', 'in:hourly,daily,weekly,manual'],
        ];
    }

    private function reglesIntegrations(): array
    {
        return [
            'google_maps_enabled' => ['nullable', 'boolean'],
            'google_maps_key'     => ['nullable', 'string', 'max:255'],
            'ga_tracking_id'      => ['nullable', 'string', 'max:50'],
            'fb_pixel_id'         => ['nullable', 'string', 'max:50'],
            'livechat_enabled'    => ['nullable', 'boolean'],
            'livechat_provider'   => ['nullable', 'string', 'max:50'],
            'livechat_token'      => ['nullable', 'string', 'max:255'],
            'social_google'       => ['nullable', 'boolean'],
            'social_facebook'     => ['nullable', 'boolean'],
            'social_github'       => ['nullable', 'boolean'],
        ];
    }

    private function reglesSecurite(): array
    {
        return [
            'two_factor_auth'            => ['nullable', 'boolean'],
            'two_factor_method'          => ['nullable', 'in:totp,sms,email'],
            'session_lifetime'           => ['nullable', 'integer', 'min:5', 'max:1440'],
            'allow_multiple_sessions'    => ['nullable', 'boolean'],
            'password_min_length'        => ['nullable', 'integer', 'min:6', 'max:32'],
            'password_require_uppercase' => ['nullable', 'boolean'],
            'password_require_number'    => ['nullable', 'boolean'],
            'password_require_special'   => ['nullable', 'boolean'],
            'password_expiry_days'       => ['nullable', 'integer', 'min:0'],
            'max_login_attempts'         => ['nullable', 'integer', 'min:1'],
            'lockout_duration'           => ['nullable', 'integer', 'min:1'],
            'ip_restriction'             => ['nullable', 'boolean'],
            'allowed_ips'                => ['nullable', 'required_if:ip_restriction,1', 'string', 'max:2000'],
            'force_https'                => ['nullable', 'boolean'],
        ];
    }

    // ─── Messages personnalisés ────────────────────────────────────────────────

    public function messages(): array
    {
        return [
            'app_name.required'      => "Le nom de l'application est obligatoire.",
            'base_currency.required' => 'La devise de base est obligatoire.',
            'email.email'            => 'Veuillez saisir une adresse e-mail valide.',
            'website.url'            => 'Veuillez saisir une URL valide (ex: https://monapp.ci).',
            'logo.image'             => 'Le logo doit être une image (PNG, JPG, SVG).',
            'logo.max'               => 'Le logo ne doit pas dépasser 2 Mo.',
            'brand_color.regex'      => 'La couleur doit être au format hexadécimal (#RRGGBB).',
        ];
    }
}
