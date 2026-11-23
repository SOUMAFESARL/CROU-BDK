<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Type de paiement paramétrable (Paramétrage hébergement › Types de paiement).
 * `code` est la valeur enregistrée dans `paiements.type_paiement` ; il ne change plus après la création.
 * Les types `systeme` (loyer, caution, pénalité, autre) sont utilisés par le code : ni supprimables ni désactivables.
 */
class TypePaiement extends Model
{
    protected $table = 'types_paiement';

    protected $primaryKey = 'id_type_paiement';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = [];

    protected $casts = [
        'actif' => 'boolean',
        'systeme' => 'boolean',
        'ordre' => 'integer',
    ];

    /** Libellés des types système, utilisés si la table n'est pas encore migrée. */
    public const PAR_DEFAUT = [
        'loyer' => 'Loyer',
        'caution' => 'Caution',
        'penalite' => 'Pénalité',
        'autre' => 'Autre',
    ];

    /** Clé du conteneur où les libellés par code sont mémorisés pour la requête en cours. */
    private const MEMOIRE = 'types_paiement.libelles';

    protected static function boot()
    {
        parent::boot();

        static::creating(function (self $type) {
            if (empty($type->id_type_paiement)) {
                $type->id_type_paiement = (string) Str::uuid();
            }
        });
        static::saved(fn () => app()->forgetInstance(self::MEMOIRE));
        static::deleted(fn () => app()->forgetInstance(self::MEMOIRE));
    }

    /** Ordre d'affichage : types système d'abord (loyer en tête), puis par nom. */
    public function scopeOrdonnes($query)
    {
        return $query->orderBy('ordre')->orderBy('name');
    }

    /** Types proposés dans le formulaire « Nouveau paiement ». */
    public static function options(): array
    {
        try {
            return self::query()->where('actif', true)->ordonnes()->get(['code', 'name'])
                ->map(fn (self $type) => ['value' => $type->code, 'label' => $type->name])->values()->all();
        } catch (\Illuminate\Database\QueryException) {
            return collect(self::PAR_DEFAUT)->map(fn ($label, $code) => ['value' => $code, 'label' => $label])->values()->all();
        }
    }

    /** Codes acceptés à l'enregistrement d'un paiement (types actifs). */
    public static function codesActifs(): array
    {
        return array_column(self::options(), 'value');
    }

    /** Libellé d'un code (types inactifs compris) ; le code brut si le type est inconnu. */
    public static function libelle(?string $code): ?string
    {
        if ($code === null || $code === '') {
            return $code;
        }
        if (! app()->bound(self::MEMOIRE)) {
            try {
                $libelles = self::query()->pluck('name', 'code')->all() + self::PAR_DEFAUT;
            } catch (\Illuminate\Database\QueryException) {
                $libelles = self::PAR_DEFAUT;
            }
            app()->instance(self::MEMOIRE, $libelles);
        }

        return app(self::MEMOIRE)[$code] ?? $code;
    }

    /** Code unique dérivé du nom (« Frais de dossier » → `frais_de_dossier`). */
    public static function codeDepuis(string $nom): string
    {
        $base = Str::limit(Str::slug($nom, '_'), 40, '') ?: 'type';
        $code = $base;
        for ($i = 2; self::query()->where('code', $code)->exists(); $i++) {
            $code = $base.'_'.$i;
        }

        return $code;
    }
}
