<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class PaymentProvider extends Model
{
    public const NETWORKS = [
        'moov' => 'Moov', 'orange' => 'Orange', 'mtn' => 'MTN',
        'wave' => 'Wave', 'cinetpay' => 'CinetPay', 'tresorpay' => 'TrésorPay',
    ];

    protected $fillable = ['api_url', 'api_key', 'merchant_key', 'is_active', 'api_user', 'subscription_key', 'request_signing_secret', 'environment', 'webhook_secret'];
    protected $hidden = ['api_url', 'api_key', 'merchant_key', 'api_user', 'subscription_key', 'request_signing_secret', 'webhook_secret'];

    protected function casts(): array
    {
        return ['api_key' => 'encrypted', 'merchant_key' => 'encrypted', 'is_active' => 'boolean',
            'api_user' => 'encrypted', 'subscription_key' => 'encrypted', 'request_signing_secret' => 'encrypted', 'webhook_secret' => 'encrypted'];
    }

    public static function payableToStudents(): Collection
    {
        return static::where('is_active', true)->whereNotNull('api_key')
            ->where(function ($q) {
                $q->where(fn ($wave) => $wave->where('code', 'wave')->whereNotNull('webhook_secret'))
                    ->orWhere(fn ($cp) => $cp->where('code', 'cinetpay')->whereNotNull('merchant_key'))
                    ->orWhere(fn ($mtn) => $mtn->where('code', 'mtn')->where('environment', 'mtnivorycoast')
                        ->whereNotNull('api_user')->whereNotNull('subscription_key'));
            })->orderBy('id')->get(['code', 'name']);
    }

    public static function visibleToStudents(): Collection
    {
        // Only public identifiers are loaded; credentials stay on the server.
        return static::query()->where('is_active', true)
            ->whereIn('code', array_keys(self::NETWORKS))
            ->orderBy('id')->get(['code', 'name']);
    }
}
