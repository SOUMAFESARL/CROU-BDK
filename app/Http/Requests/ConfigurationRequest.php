<?php

namespace App\Http\Requests;

use App\Http\Controllers\admin\parametrage\configurationController;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class ConfigurationRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Accès contrôlé par la permission de la route (config/admin_permissions.php).
        return true;
    }

    private function menu(): string
    {
        return (string) ($this->route('menus') ?? $this->query('menus', 'configuration'));
    }

    protected function prepareForValidation(): void
    {
        if ($this->menu() === 'profil') {
            \App\Support\Telephone::normaliserRequete($this, ['phone']);
        }
    }

    public function rules(): array
    {
        return match ($this->menu()) {
            'profil'        => $this->reglesProfil(),
            'notifications' => $this->reglesNotifications(),
            'paiements'     => $this->reglesPaiements(),
            'taxes'         => $this->reglesTaxes(),
            'devises'       => $this->reglesDevises(),
            'securite'      => $this->reglesSecurite(),
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
            'phone'        => ['nullable', \App\Support\Telephone::regle()],
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

    /** Réseaux et clés des passerelles : page « API de paiement » (PaymentProviderController). */
    private function reglesPaiements(): array
    {
        return [
            'methods'                => ['nullable', 'array'],
            'methods.*'              => ['in:cash,mobile,transfer,card'],
            'online_payment_enabled' => ['nullable', 'boolean'],
            'auto_receipt'           => ['nullable', 'boolean'],
            'min_amount'             => ['required', 'integer', 'min:0', 'max:100000000'],
            'daily_limit'            => ['required', 'integer', 'min:0', 'max:1000000000'],
        ];
    }

    private function reglesTaxes(): array
    {
        return [
            'tva_enabled'       => ['nullable', 'boolean'],
            'tva_rate'          => ['required', 'numeric', 'min:0', 'max:100'],
            'tva_number'        => ['nullable', 'string', 'max:60'],
            'price_display'     => ['required', 'in:TTC,HT'],
            'tva_on_services'   => ['nullable', 'boolean'],
            'digital_tax_rate'  => ['required', 'numeric', 'min:0', 'max:100'],
            'service_fee'       => ['required', 'integer', 'min:0'],
        ];
    }

    private function reglesDevises(): array
    {
        return [
            'base_currency'          => ['required', 'in:XOF,XAF,EUR,USD,GHS,NGN,KES'],
            'currency_symbol'        => ['required', 'string', 'max:10'],
            'symbol_position'        => ['required', 'in:before,after'],
            'thousands_separator'    => ['required', Rule::in(array_keys(configurationController::SEPARATEURS))],
            'secondary_currencies'   => ['nullable', 'array'],
            'secondary_currencies.*' => ['in:XOF,XAF,EUR,USD,GHS,NGN,KES,GBP'],
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

    /** La méthode 2FA est forcée à « email » par le contrôleur (seule méthode branchée dans AuthController). */
    private function reglesSecurite(): array
    {
        return [
            'two_factor_auth'            => ['nullable', 'boolean'],
            'session_lifetime'           => ['required', 'integer', 'min:5', 'max:1440'],
            'allow_multiple_sessions'    => ['nullable', 'boolean'],
            'password_min_length'        => ['required', 'integer', 'min:6', 'max:32'],
            'password_require_uppercase' => ['nullable', 'boolean'],
            'password_require_number'    => ['nullable', 'boolean'],
            'password_require_special'   => ['nullable', 'boolean'],
            'max_login_attempts'         => ['required', 'integer', 'min:1', 'max:100'],
            'lockout_duration'           => ['required', 'integer', 'min:1', 'max:1440'],
            'ip_restriction'             => ['nullable', 'boolean'],
            'allowed_ips'                => ['nullable', 'required_if_accepted:ip_restriction', 'string', 'max:2000'],
            'force_https'                => ['nullable', 'boolean'],
        ];
    }

    /** Garde-fous de la section Sécurité : l'administrateur ne doit pas se couper lui-même l'accès. */
    public function withValidator(Validator $validator): void
    {
        if ($this->menu() !== 'securite') {
            return;
        }

        $validator->after(function (Validator $validator) {
            if ($this->boolean('ip_restriction')) {
                $ips = collect(preg_split('/[\s,;]+/', (string) $this->input('allowed_ips')))->filter();
                if ($ips->first(fn ($ip) => !filter_var($ip, FILTER_VALIDATE_IP))) {
                    $validator->errors()->add('allowed_ips', 'Saisissez des adresses IP valides, séparées par des virgules ou des retours à la ligne.');
                } elseif ($ips->isNotEmpty() && !$ips->contains($this->ip())) {
                    $validator->errors()->add('allowed_ips', 'Ajoutez votre adresse IP actuelle ('.$this->ip().') à la liste, sinon vous perdrez l’accès.');
                }
            }
            if ($this->boolean('force_https') && !$this->secure()) {
                $validator->errors()->add('force_https', 'Activez « Forcer HTTPS » depuis une connexion HTTPS uniquement : sinon l’application deviendrait inaccessible.');
            }
        });
    }

    // ─── Messages personnalisés ────────────────────────────────────────────────

    public function messages(): array
    {
        return [
            'app_name.required'      => "Le nom de l'application est obligatoire.",
            'base_currency.required' => 'La devise de base est obligatoire.',
            'allowed_ips.required_if_accepted' => 'Indiquez au moins une adresse IP autorisée.',
            'email.email'            => 'Veuillez saisir une adresse e-mail valide.',
            'website.url'            => 'Veuillez saisir une URL valide (ex: https://monapp.ci).',
            'logo.image'             => 'Le logo doit être une image (PNG, JPG, SVG).',
            'logo.max'               => 'Le logo ne doit pas dépasser 2 Mo.',
            'brand_color.regex'      => 'La couleur doit être au format hexadécimal (#RRGGBB).',
        ];
    }
}
