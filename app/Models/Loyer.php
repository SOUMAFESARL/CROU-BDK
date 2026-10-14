<?php
// app/Models/Loyer.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Support\Str;

class Loyer extends Model
{
    use SoftDeletes, HasUuids;

    protected $table = 'loyers';
    protected $primaryKey = 'id_loyer';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'etudiant_id',
        'chambre_id',
        'cite_id',
        'assignation_id',
        'campus_id',
        'montant_base',
        'montant_total',
        'caution',
        'frais_inscription',
        'annee_universitaire',
        'periode',
        'nombre_echeances',
        'montant_echeance',
        'statut',
        'date_paiement',
       
        'date_limit_paiement',
        'mois_facturation',
        'annee_facturation',
        'boursier',
        'reduction_bourse',
        'type_bourse',
        'reduction_sociale',
        'justificatif_reduction',
        'supplement_prestation',
        'detail_supplements',
        'total_paye',
        'solde_restant',
        'nombre_impayes',
        'date_dernier_paiement',
        'penalite_journaliere',
        'total_penalites',
        'jours_retard_autorises',
        'renouvellement_auto',
        'periode_rappel_jours',
        'notes',
        'metadata',
        'created_by',
        'updated_by',
        'deleted_by'
    ];

    protected $casts = [
        'montant_base' => 'decimal:2',
        'montant_total' => 'decimal:2',
        'caution' => 'decimal:2',
        'frais_inscription' => 'decimal:2',
        'montant_echeance' => 'decimal:2',
        'reduction_bourse' => 'decimal:2',
        'reduction_sociale' => 'decimal:2',
        'supplement_prestation' => 'decimal:2',
        'total_paye' => 'decimal:2',
        'solde_restant' => 'decimal:2',
        'penalite_journaliere' => 'decimal:2',
        'total_penalites' => 'decimal:2',
        'boursier' => 'boolean',
        'renouvellement_auto' => 'boolean',
        'date_debut' => 'date',
        'date_paiement' => 'date',
        'date_limit_paiement' => 'date',
        'date_dernier_paiement' => 'date',
        'metadata' => 'json',
        'detail_supplements' => 'json',
    ];

    protected $attributes = [
    'montant_base' => 0,
    'montant_total' => 0,
    'montant_echeance' => 0,
    'nombre_impayes' => 0,
    'total_penalites' => 0,
    'total_paye' => 0,
    'solde_restant' => 0,
    'penalite_journaliere' => 0,
    'jours_retard_autorises' => 5,
    'renouvellement_auto' => true,
    'boursier' => false,
    'reduction_bourse' => 0,
    'reduction_sociale' => 0,
    'supplement_prestation' => 0,
];

 protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->{$model->getKeyName()})) {
                $model->{$model->getKeyName()} = (string) Str::uuid();
            }

            if (empty($model->created_by)) {
                $model->created_by = getInfoAdmin()->users->id_user ?? 'system';
            }

            if (empty($model->campus_id)) {
                $model->campus_id = getInfoAdmin()->users->campus_id ?? null;
            }


        });

        static::updating(function ($model) {


            if (empty($model->updated_by)) {
                $model->updated_by = getInfoAdmin()->users->id_user ?? 'system';
            }

        });

  static::deleting(function ($model) {
            if (empty($model->deleted_by)) {
                $model->deleted_by = getInfoAdmin()->users->id_user ?? 'system';
            }
            
            // Marquer comme annulé avant de soft delete
            $model->statut = 'annule';
            $model->saveQuietly(); // Sauvegarde sans déclencher les événements
        });

    }

    // Relations
    public function etudiant()
    {
        return $this->belongsTo(Etudiant::class, 'etudiant_id', 'id_etudiant');
    }

    public function chambre()
    {
        return $this->belongsTo(Chambre::class, 'chambre_id', 'id_chambre');
    }

    public function cite()
    {
        return $this->belongsTo(Cite::class, 'cite_id', 'id_cite');
    }

    public function assignation()
    {
        return $this->belongsTo(AssignationChambre::class, 'assignation_id', 'id_assignation');
    }

    public function paiements()
    {
        return $this->hasMany(Paiement::class, 'loyer_id', 'id_loyer');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by', 'id_user');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by', 'id_user');
    }

    // Scopes
    public function scopeActif($query)
    {
        return $query->where('statut', 'actif');
    }

    public function scopeEnRetard($query)
    {
        return $query->where('statut', 'en_retard')
            ->orWhere(function ($q) {
                $q->where('date_prochain_paiement', '<', now())
                    ->where('solde_restant', '>', 0);
            });
    }

    public function scopePourAnnee($query, $annee)
    {
        return $query->where('annee_universitaire', $annee);
    }

    // Méthodes de calcul
    public function calculerSolde()
    {
        $this->solde_restant = $this->montant_total + $this->total_penalites - $this->total_paye;
        return $this->solde_restant;
    }

    public function appliquerPenalites()
    {
        if ($this->date_limit_paiement && $this->date_limit_paiement < now() && $this->solde_restant > 0) {
            $joursRetard = now()->diffInDays($this->date_limit_paiement) - $this->jours_retard_autorises;
            if ($joursRetard > 0 && $this->penalite_journaliere > 0) {
                $this->total_penalites += $joursRetard * $this->penalite_journaliere;
                $this->calculerSolde();
                $this->save();
            }
        }
    }

    public function enregistrerPaiement($montant, $datePaiement = null)
    {
        $this->total_paye += $montant;
        $this->date_dernier_paiement = $datePaiement ?? now();
        $this->calculerSolde();
        
        if ($this->solde_restant <= 0) {
            $this->statut = 'termine';
            $this->date_prochain_paiement = null;
        } else {
            $this->mettreAJourProchaineEcheance();
        }
        
        $this->save();
    }

     public const PERIODE = [
       'annuel','semestriel','trimestriel','mensuel'
    ];

    public const STATUT = [
         'actif','en_retard','suspendu','termine','annule'
    ];


    protected function mettreAJourProchaineEcheance()
    {
        $intervalle = match($this->periode) {
            'mensuel' => '+1 month',
            'trimestriel' => '+3 months',
            'semestriel' => '+6 months',
            'annuel' => '+1 year',
        };
        
        $this->date_limit_paiement = ($this->date_limit_paiement ?? $this->date_debut)
            ? date('Y-m-d', strtotime($intervalle, strtotime($this->date_limit_paiement)))
            : null;
    }
}