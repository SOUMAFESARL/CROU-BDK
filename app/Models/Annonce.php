<?php
// app/Models/Annonce.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Annonce extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'annonces';
    protected $primaryKey = 'id_annonce';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id_annonce',
        'titre',
        'contenu',
        'canaux',
        'audience',
        'audience_cible',
        'statut',
        'priorite',
        'date_publication',
        'date_expiration',
        'date_planification',
        'vues',
        'clics',
        'reactions',
        'pieces_jointes',
        'created_by',
        'updated_by',
        'deleted_by'
    ];

    protected $casts = [
        'canaux' => 'array',
        'audience_cible' => 'array',
        'pieces_jointes' => 'array',
        'date_publication' => 'datetime',
        'date_expiration' => 'datetime',
        'date_planification' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    protected $attributes = [
        'statut' => 'brouillon',
        'priorite' => 'normale',
        'vues' => 0,
        'clics' => 0,
        'reactions' => 0,
    ];

    // Constantes
    const STATUTS = ['brouillon', 'publie', 'planifie', 'annule'];
    const PRIORITES = ['normale', 'haute', 'urgente'];
    const CANAUX = ['flux', 'sms', 'email'];
    const AUDIENCES = ['all', 'cite', 'batiment', 'chambre', 'specific'];

    /**
     * Boot du modèle
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->id_annonce)) {
                $model->id_annonce = (string) Str::uuid();
            }
            if (empty($model->created_by)) {
                $model->created_by = getInfoAdmin()->users->code ?? null;
            }
        });
    }

    protected static function booted(): void
    {
        static::saving(function ($annonce) {
            if ($annonce->statut === 'publie' && !$annonce->date_publication) $annonce->date_publication = now();
        });
        static::saved(function ($annonce) {
            if ($annonce->statut === 'publie' && ($annonce->wasRecentlyCreated || $annonce->wasChanged('statut'))) {
                app(\App\Services\AnnonceDiffusionService::class)->prepare($annonce->id_annonce);
                if (!app()->runningInConsole()) {
                    $id = $annonce->id_annonce;
                    app()->terminating(fn () => app(\App\Services\AnnonceDiffusionService::class)->sendPending($id));
                }
            }
        });
    }

    public function deliveries()
    {
        return $this->hasMany(AnnonceDelivery::class, 'annonce_id', 'id_annonce');
    }

    /**
     * Relations
     */
    public function createur()
    {
        return $this->belongsTo(User::class, 'created_by', 'code');
    }

    public function moderateur()
    {
        return $this->belongsTo(User::class, 'updated_by', 'id');
    }

    public function stats()
    {
        return $this->hasMany(AnnonceStat::class, 'annonce_id', 'id_annonce');
    }

    public function notifications()
    {
        return $this->hasMany(AnnonceNotification::class, 'annonce_id', 'id_annonce');
    }

    /**
     * Scopes
     */
    public function scopePublie($query)
    {
        return $query->where('statut', 'publie')
            ->where('date_publication', '<=', now());
    }

    public function scopePlanifie($query)
    {
        return $query->where('statut', 'planifie')
            ->where('date_planification', '>', now());
    }

    public function scopeBrouillon($query)
    {
        return $query->where('statut', 'brouillon');
    }

    public function scopeActif($query)
    {
        return $query->where('statut', 'publie')
            ->where('date_publication', '<=', now())
            ->where(function ($q) {
                $q->whereNull('date_expiration')
                    ->orWhere('date_expiration', '>', now());
            });
    }

    public function scopeUrgent($query)
    {
        return $query->where('priorite', 'urgente');
    }

    public function scopeParCanal($query, $canal)
    {
        return $query->whereJsonContains('canaux', $canal);
    }

    public function scopeParAudience($query, $audience)
    {
        return $query->where('audience', $audience);
    }

    /**
     * Accesseurs
     */
    public function getCanauxLibellesAttribute()
    {
        $labels = [
            'flux' => 'Flux d\'actualité',
            'sms' => 'SMS',
            'email' => 'Email',
        ];
        return collect($this->canaux)->map(fn($c) => $labels[$c] ?? $c)->implode(', ');
    }

    public function getStatutLibelleAttribute()
    {
        $labels = [
            'brouillon' => 'Brouillon',
            'publie' => 'Publié',
            'planifie' => 'Planifié',
            'annule' => 'Annulé'
        ];
        return $labels[$this->statut] ?? $this->statut;
    }

    public function getPrioriteLibelleAttribute()
    {
        $labels = [
            'normale' => 'Normale',
            'haute' => 'Haute',
            'urgente' => 'Urgente'
        ];
        return $labels[$this->priorite] ?? $this->priorite;
    }

    public function getAudienceLibelleAttribute()
    {
        $labels = [
            'all' => 'Tous les étudiants',
            'cite' => 'Par cité',
            'batiment' => 'Par bâtiment',
            'chambre' => 'Par chambre',
            'specific' => 'Sélection spécifique'
        ];
        return $labels[$this->audience] ?? $this->audience;
    }

    public function getEstPublieeAttribute()
    {
        return $this->statut === 'publie' && $this->date_publication <= now();
    }

    public function getEstExpireeAttribute()
    {
        return $this->date_expiration && $this->date_expiration < now();
    }

    /**
     * Méthodes métier
     */
    public function publier()
    {
        $this->statut = 'publie';
        $this->date_publication = now();
        return $this->save();
    }

    public function planifier($date)
    {
        $this->statut = 'planifie';
        $this->date_planification = $date;
        return $this->save();
    }

    public function annuler()
    {
        $this->statut = 'annule';
        return $this->save();
    }

    public function incrementerVues()
    {
        return $this->increment('vues');
    }

    public function incrementerClics()
    {
        return $this->increment('clics');
    }

    public function incrementerReactions()
    {
        return $this->increment('reactions');
    }

    public function getDestinataires()
    {
        return app(\App\Services\AnnonceAudience::class)->recipients($this)->pluck('id_etudiant');
    }

    public function scopeVisibleParEtudiant($query, $etudiant)
    {
        return app(\App\Services\AnnonceAudience::class)->visible($query, $etudiant);
    }
}
