<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OnlinePayment extends Model
{
    public $incrementing = false;
    protected $keyType = 'string';
    protected $guarded = [];
    protected $hidden = ['credentials', 'callback_token_hash'];

    protected function casts(): array
    {
        return ['credentials' => 'encrypted:array', 'amount' => 'integer',
            'next_check_at' => 'datetime', 'notified_at' => 'datetime', 'verified_at' => 'datetime'];
    }

    public function provider()
    {
        return $this->belongsTo(PaymentProvider::class);
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'succeeded' => 'Paiement confirmé',
            'failed' => 'Paiement non effectué',
            'review' => 'Vérification par le service financier nécessaire',
            default => 'Paiement en attente de confirmation',
        };
    }
}
