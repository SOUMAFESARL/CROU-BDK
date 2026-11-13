<?php

namespace App\Http\Services;

use App\Models\ParametreConduite;
use App\Models\Sanction;
use App\Models\TypeSanction;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Paramétrage des sanctions (docs/parametrage-sanctions.md) : types de sanction utilisés dans tout le système
 * (décision d'un incident disciplinaire, sanction directe) et note de conduite initiale des étudiants.
 *
 * Chaque type retire un nombre de points à la note de conduite et a un **effet** :
 * - `aucun` : retrait de points uniquement ;
 * - `suspension` : bloque admissions, attributions et confirmations jusqu'à la date de fin (période obligatoire) ;
 * - `expulsion` : bloque, annule les réservations et prépare la sortie du logement.
 *
 * Le comportement du système dépend de l'effet, jamais du code : un type ajouté se comporte comme ceux d'origine.
 * Lu une fois par requête (service à portée de requête, `AppServiceProvider`) ; tant que la table n'existe pas,
 * le barème d'origine s'applique.
 */
class CatalogueSanctions
{
    public const EFFETS = [
        'aucun' => 'Retrait de points uniquement',
        'suspension' => 'Suspension temporaire',
        'expulsion' => 'Expulsion du logement',
    ];

    /** Effets qui bloquent nouvelles admissions et attributions tant que la sanction est active. */
    public const EFFETS_BLOQUANTS = ['suspension', 'expulsion'];

    /** Décision « sans sanction » d'un incident : code réservé, jamais un type de sanction. */
    public const AUCUNE = 'aucune';

    /** Barème d'origine (repris par la migration du paramétrage). */
    public const DEFAUTS = [
        ['code' => 'avertissement', 'libelle' => 'Avertissement', 'points_retires' => 2.0, 'effet' => 'aucun'],
        ['code' => 'blame', 'libelle' => 'Blâme', 'points_retires' => 5.0, 'effet' => 'aucun'],
        ['code' => 'suspension', 'libelle' => 'Suspension', 'points_retires' => 10.0, 'effet' => 'suspension'],
        ['code' => 'expulsion', 'libelle' => 'Expulsion', 'points_retires' => 20.0, 'effet' => 'expulsion'],
    ];

    /** Bornes de la note de conduite et des points retirés. */
    public const NOTE_MAX = 100;

    private ?Collection $types = null;

    private ?float $noteInitiale = null;

    /**
     * Tous les types (actifs ou non : les sanctions passées gardent leur libellé), les plus lourds d'abord.
     *
     * @return Collection<int, array{id: ?string, code: string, libelle: string, points_retires: float, effet: string, description: ?string, actif: bool}>
     */
    public function tous(): Collection
    {
        return $this->types ??= $this->disponible()
            ? TypeSanction::query()->get()->map(fn (TypeSanction $type) => [
                'id' => (string) $type->getKey(),
                'code' => (string) $type->code,
                'libelle' => (string) $type->libelle,
                'points_retires' => (float) $type->points_retires,
                'effet' => array_key_exists((string) $type->effet, self::EFFETS) ? (string) $type->effet : 'aucun',
                'description' => $type->description,
                'actif' => (bool) $type->actif,
            ])->pipe(fn (Collection $types) => $this->trier($types))
            : $this->trier(collect(self::DEFAUTS)->map(fn (array $type) => $type + ['id' => null, 'description' => null, 'actif' => true]));
    }

    /** Types proposés aux nouvelles décisions. */
    public function actifs(): Collection
    {
        return $this->tous()->where('actif', true)->values();
    }

    public function type(?string $code): ?array
    {
        return $code === null ? null : $this->tous()->firstWhere('code', $code);
    }

    /** Libellé d'un code (« Aucune sanction » pour la décision sans sanction ; code lisible si le type a été supprimé). */
    public function libelle(?string $code): string
    {
        if ($code === null || $code === self::AUCUNE) {
            return 'Aucune sanction';
        }

        return $this->type($code)['libelle'] ?? Str::of($code)->replace('_', ' ')->ucfirst()->toString();
    }

    public function effet(?string $code): string
    {
        return $this->type($code)['effet'] ?? 'aucun';
    }

    public function estBloquant(?string $code): bool
    {
        return in_array($this->effet($code), self::EFFETS_BLOQUANTS, true);
    }

    /** Points retirés par un type (0 pour un type inconnu). */
    public function points(?string $code): float
    {
        return (float) ($this->type($code)['points_retires'] ?? 0);
    }

    /**
     * Codes ayant un effet donné (actifs ou non : une sanction ancienne garde son effet).
     *
     * @return list<string>
     */
    public function codesParEffet(string $effet, bool $actifsSeulement = false): array
    {
        return ($actifsSeulement ? $this->actifs() : $this->tous())->where('effet', $effet)->pluck('code')->values()->all();
    }

    /** Options d'une liste de choix : libellé, points retirés et effet. */
    public function options(bool $avecAucune = false, bool $sansExpulsion = false): array
    {
        return collect($avecAucune ? [['value' => self::AUCUNE, 'label' => 'Aucune sanction', 'description' => 'Aucun point retiré', 'effet' => 'aucun', 'points' => 0.0]] : [])
            ->merge($this->actifs()
                ->when($sansExpulsion, fn (Collection $types) => $types->where('effet', '!=', 'expulsion'))
                ->map(fn (array $type) => [
                    'value' => $type['code'],
                    'label' => $type['libelle'],
                    'description' => '−'.self::formatPoints($type['points_retires']).' point(s) de conduite',
                    'effet' => $type['effet'],
                    'points' => $type['points_retires'],
                ]))
            ->values()->all();
    }

    public function noteInitiale(): float
    {
        return $this->noteInitiale ??= Schema::hasTable('parametres_conduite')
            ? (float) ParametreConduite::current()->note_initiale
            : (float) ParametreConduite::NOTE_INITIALE_DEFAUT;
    }

    public function definirNoteInitiale(float $note, ?string $auteur): void
    {
        ParametreConduite::current()->update(['note_initiale' => round($note, 2), 'updated_by' => $auteur]);
        $this->oublier();
    }

    /** Crée un type ; son code est déduit du libellé (unique, distinct de « aucune »). */
    public function creer(array $donnees, ?string $auteur): TypeSanction
    {
        $this->verifierLibelleLibre($donnees['libelle']);
        $type = TypeSanction::create([
            'code' => $this->nouveauCode($donnees['libelle']),
            'libelle' => $donnees['libelle'],
            'points_retires' => round((float) $donnees['points_retires'], 2),
            'effet' => $donnees['effet'],
            'description' => $donnees['description'] ?? null,
            'actif' => (bool) ($donnees['actif'] ?? true),
            'created_by' => $auteur,
        ]);
        $this->oublier();

        return $type;
    }

    /**
     * Modifie un type. Le code ne change jamais ; l'effet d'un type déjà appliqué est figé (les sanctions
     * existantes en dépendent : blocages, sortie du logement). Les points retirés ne s'appliquent qu'aux sanctions futures.
     */
    public function modifier(TypeSanction $type, array $donnees, ?string $auteur): void
    {
        $this->verifierLibelleLibre($donnees['libelle'], $type);
        if ($donnees['effet'] !== $type->effet && $this->utilisations($type) > 0) {
            throw ValidationException::withMessages(['effet' => 'Ce type a déjà été appliqué : son effet ne peut plus être modifié. Créez un nouveau type si besoin.']);
        }
        $type->update([
            'libelle' => $donnees['libelle'],
            'points_retires' => round((float) $donnees['points_retires'], 2),
            'effet' => $donnees['effet'],
            'description' => $donnees['description'] ?? null,
            'actif' => (bool) $donnees['actif'],
            'updated_by' => $auteur,
        ]);
        $this->oublier();
    }

    /** Supprime un type jamais appliqué ; sinon, il se désactive pour rester lisible dans l'historique. */
    public function supprimer(TypeSanction $type): void
    {
        if (($total = $this->utilisations($type)) > 0) {
            throw ValidationException::withMessages(['type' => "Ce type a déjà été appliqué à {$total} sanction(s) : désactivez-le plutôt que de le supprimer."]);
        }
        $type->delete();
        $this->oublier();
    }

    /** Nombre de sanctions enregistrées avec ce type. */
    public function utilisations(TypeSanction $type): int
    {
        return Sanction::where('type', $type->code)->count();
    }

    /** Recharge le paramétrage après une modification. */
    public function oublier(): void
    {
        $this->types = null;
        $this->noteInitiale = null;
    }

    /** « 2 », « 2,5 » : points sans décimales inutiles. */
    public static function formatPoints(float $points): string
    {
        return str_replace('.', ',', rtrim(rtrim(number_format($points, 2, '.', ''), '0'), '.'));
    }

    private function disponible(): bool
    {
        return Schema::hasTable('types_sanction');
    }

    /** Expulsion, puis suspension, puis points retirés décroissants. */
    private function trier(Collection $types): Collection
    {
        $rang = ['expulsion' => 0, 'suspension' => 1, 'aucun' => 2];

        return $types->sort(fn (array $a, array $b) => [$rang[$a['effet']] ?? 2, -$a['points_retires'], $a['libelle']]
            <=> [$rang[$b['effet']] ?? 2, -$b['points_retires'], $b['libelle']])->values();
    }

    private function verifierLibelleLibre(string $libelle, ?TypeSanction $sauf = null): void
    {
        $existe = TypeSanction::query()
            ->whereRaw('LOWER(libelle) = ?', [mb_strtolower(trim($libelle))])
            ->when($sauf, fn ($q) => $q->whereKeyNot($sauf->getKey()))
            ->exists();
        if ($existe) {
            throw ValidationException::withMessages(['libelle' => 'Un type de sanction porte déjà ce libellé.']);
        }
    }

    private function nouveauCode(string $libelle): string
    {
        $base = Str::limit(Str::slug($libelle, '_'), 26, '') ?: 'sanction';
        $code = $base;
        for ($i = 2; $code === self::AUCUNE || TypeSanction::where('code', $code)->exists(); $i++) {
            $code = $base.'_'.$i;
        }

        return $code;
    }
}
