<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;
use Illuminate\Support\Str;

class ConfigApp extends Model
{
  //  use SoftDeletes;

    protected $table = 'config_app';

    protected $primaryKey = 'config_id';
    public $timestamps = true;
    protected $keyType = 'string';  

    protected $fillable = [
        'app_name',
        'locale',
        'timezone',
        'date_format',
        'maintenance_mode',
        'public_registration',
        'activity_log',
        'force_dark_mode',
        'company_name',
        'country',
        'phone',
        'email',
        'website',
        'address',
        'rccm',
        'ncc',
        'sector',
        'region_id',
        'ville_id',
        'logo',
        'brand_color',
        'two_factor_auth',
        'two_factor_method',
        'session_lifetime',
        'allow_multiple_sessions',
        'password_min_length',
        'password_require_uppercase',
        'password_require_number',
        'password_require_special',
        'password_expiry_days',
        'max_login_attempts',
        'lockout_duration',
        'ip_restriction',
        'allowed_ips',
        'force_https',
        'tva_enabled',
        'tva_rate',
        'tva_number',
        'price_display',
        'tva_on_services',
        'digital_tax_rate',
        'service_fee',
        'tax_on_delivery',
        'google_maps_enabled',
        'google_maps_key',
        'ga_tracking_id',
        'fb_pixel_id',
        'livechat_enabled',
        'livechat_provider',
        'livechat_token',
        'social_google',
        'social_facebook',
        'social_github',
        'methods',
        'online_payment_enabled',
        'payment_gateway',
        'api_key_public',
        'api_key_secret',
        'sandbox_mode',
        'currency',
        'auto_receipt',
        'min_amount',
        'three_d_secure',
        'anti_fraud',
        'daily_limit',
        'email_enabled',
        'mail_driver',
        'mail_host',
        'mail_port',
        'mail_username',
        'mail_password',
        'mail_from_name',
        'mail_from_address',
        'sms_enabled',
        'sms_provider',
        'sms_api_url',
        'sms_api_key',
        'sms_sender',
        'notif_new_user',
        'notif_new_order',
        'notif_payment_ok',
        'notif_payment_fail',
        'notif_low_stock',
        'notif_report',
        'base_currency',
        'currency_symbol',
        'symbol_position',
        'thousands_separator',
        'secondary_currencies',
        'auto_rate',
        'rate_frequency',
         'created_by' ,
        'updated_by'
    ];

    protected $casts = [
        'maintenance_mode' => 'boolean',
        'public_registration' => 'boolean',
        'activity_log' => 'boolean',
        'force_dark_mode' => 'boolean',
        'two_factor_auth' => 'boolean',
        'allow_multiple_sessions' => 'boolean',
        'password_require_uppercase' => 'boolean',
        'password_require_number' => 'boolean',
        'password_require_special' => 'boolean',
        'ip_restriction' => 'boolean',
        'force_https' => 'boolean',
        'tva_enabled' => 'boolean',
        'tva_on_services' => 'boolean',
        'tax_on_delivery' => 'boolean',
        'google_maps_enabled' => 'boolean',
        'livechat_enabled' => 'boolean',
        'social_google' => 'boolean',
        'social_facebook' => 'boolean',
        'social_github' => 'boolean',
        'online_payment_enabled' => 'boolean',
        'sandbox_mode' => 'boolean',
        'auto_receipt' => 'boolean',
        'three_d_secure' => 'boolean',
        'anti_fraud' => 'boolean',
        'email_enabled' => 'boolean',
        'sms_enabled' => 'boolean',
        'notif_new_user' => 'boolean',
        'notif_new_order' => 'boolean',
        'notif_payment_ok' => 'boolean',
        'notif_payment_fail' => 'boolean',
        'notif_low_stock' => 'boolean',
        'notif_report' => 'boolean',
        'auto_rate' => 'boolean',
        'methods' => 'array',
        'secondary_currencies' => 'array',
        'tva_rate' => 'decimal:2',
        'digital_tax_rate' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($config) {
            if (empty($config->{$config->getKeyName()})) {
                $config->{$config->getKeyName()} = (string) Str::uuid();
            }

            if (empty($config->created_by)) {
                $config->created_by = getInfoAdmin()->users->id_user ?? null;
            }



        });

        static::updating(function ($config) {


            if (empty($config->updated_by)) {
                $config->updated_by = getInfoAdmin()->users->id_user ?? null;
            }

        });
    }
    // Accesseurs
    public function getFullCompanyNameAttribute(): string
    {
        return $this->company_name ?? $this->app_name;
    }

    public function getCurrencyFormattedAttribute(): string
    {
        return $this->currency_symbol . ' (' . $this->currency . ')';
    }

    public function getIsMaintenanceAttribute(): bool
    {
        return (bool) $this->maintenance_mode;
    }

    public function getPaymentMethodsAttribute(): array
    {
        return $this->methods ?? ['card', 'mobile'];
    }

    // Mutateurs
    public function setMethodsAttribute($value)
    {
        $this->attributes['methods'] = json_encode($value);
    }

    public function setSecondaryCurrenciesAttribute($value)
    {
        $this->attributes['secondary_currencies'] = json_encode($value);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('maintenance_mode', false);
    }

    public function scopeByAppName($query, string $appName)
    {
        return $query->where('app_name', 'LIKE', "%{$appName}%");
    }

    public function scopeWithPaymentEnabled($query)
    {
        return $query->where('online_payment_enabled', true);
    }

    public function scopeWithEmailEnabled($query)
    {
        return $query->where('email_enabled', true);
    }

    // Méthodes utilitaires
    public function isTwoFactorEnabled(): bool
    {
        return (bool) $this->two_factor_auth;
    }

    public function getTwoFactorMethod(): string
    {
        return $this->two_factor_method ?? 'totp';
    }

    public function getTvaRate(): float
    {
        return (float) $this->tva_rate;
    }

    public function getSessionLifetimeInMinutes(): int
    {
        return (int) $this->session_lifetime;
    }

    public function getPasswordRequirements(): array
    {
        return [
            'min_length' => $this->password_min_length ?? 8,
            'require_uppercase' => (bool) $this->password_require_uppercase,
            'require_number' => (bool) $this->password_require_number,
            'require_special' => (bool) $this->password_require_special,
            'expiry_days' => $this->password_expiry_days ?? 0
        ];
    }

    public function getPaymentGatewayConfig(): array
    {
        return [
            'gateway' => $this->payment_gateway ?? 'cinetpay',
            'public_key' => $this->api_key_public,
            'secret_key' => $this->api_key_secret,
            'sandbox' => (bool) $this->sandbox_mode,
            'currency' => $this->currency ?? 'XOF',
            'min_amount' => (int) $this->min_amount ?? 500,
            'daily_limit' => (int) $this->daily_limit ?? 500000
        ];
    }

    public function getMailConfig(): array
    {
        return [
            'driver' => $this->mail_driver ?? 'smtp',
            'host' => $this->mail_host,
            'port' => (int) $this->mail_port ?? 587,
            'username' => $this->mail_username,
            'password' => $this->mail_password,
            'from_name' => $this->mail_from_name ?? $this->company_name ?? $this->app_name,
            'from_address' => $this->mail_from_address ?? $this->email
        ];
    }

    public function getNotificationPreferences(): array
    {
        return [
            'new_user' => (bool) $this->notif_new_user,
            'new_order' => (bool) $this->notif_new_order,
            'payment_ok' => (bool) $this->notif_payment_ok,
            'payment_fail' => (bool) $this->notif_payment_fail,
            'low_stock' => (bool) $this->notif_low_stock,
            'report' => (bool) $this->notif_report
        ];
    }

    public function region()
    {
        return $this->belongsTo(Region::class, 'region_id', 'id');
    }
    public function ville()
    {
        return $this->belongsTo(Ville::class, 'ville_id', 'id');
    }
}
