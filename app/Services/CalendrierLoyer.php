<?php

namespace App\Services;

use App\Http\Services\PaiementService;
use App\Models\AssignationChambre;
use App\Models\Campagne;
use App\Models\DemandeEtudiant;
use App\Models\Loyer;
use App\Models\Paiement;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Calendrier des loyers d'une campagne (onglet « Calendrier des loyers » de Paiements) : les 10 mois de loyer payables
 * et la date d'échéance de chacun, enregistrés dans `campagne.calendrier_loyer` (`[{mois: aaaa-mm, echeance: aaaa-mm-jj}]`).
 *
 * Une fois enregistré, il fixe les mois proposés au paiement (loyer, avance), leur ordre et la date limite des loyers ;
 * sans calendrier, les mois se suivent et l'échéance tombe le 10 du mois. Aucune pénalité de retard ne s'applique.
 */
class CalendrierLoyer
{
    /** Nombre de mois de loyer d'une campagne. */
    public const NOMBRE_MOIS = 10;

    /** Jour d'échéance par défaut (sans calendrier, ou proposé pour un nouveau calendrier). */
    public const JOUR_ECHEANCE = 10;

    /** Premier mois proposé pour un nouveau calendrier : septembre, début de l'année universitaire. */
    private const MOIS_DEBUT_PROPOSE = 9;

    private const MOIS = [1 => 'janvier', 'février', 'mars', 'avril', 'mai', 'juin', 'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre'];

    /**
     * Calendrier enregistré de la campagne, du premier au dernier mois ; `[]` s'il n'a pas été configuré.
     *
     * @return list<array{mois: string, echeance: string}>
     */
    public function de(?Campagne $campagne): array
    {
        $liste = $campagne?->calendrier_loyer;
        if (! is_array($liste)) {
            return [];
        }
        $mois = collect($liste)
            ->filter(fn ($entree) => is_array($entree) && self::cleValide($entree['mois'] ?? null) && self::dateValide($entree['echeance'] ?? null))
            ->map(fn (array $entree) => ['mois' => $entree['mois'], 'echeance' => $entree['echeance']])
            ->sortBy('mois')->values()->all();

        return count($mois) === self::NOMBRE_MOIS ? $mois : [];
    }

    /**
     * Calendrier affiché dans l'onglet : celui enregistré, sinon une proposition (10 mois à partir de septembre de
     * l'année de début de la campagne, échéance le 10 du mois).
     *
     * @return list<array{mois: string, echeance: string}>
     */
    public function propose(Campagne $campagne): array
    {
        if ($enregistre = $this->de($campagne)) {
            return $enregistre;
        }
        $regle = $this->regle($campagne);

        return self::construire($regle['premier_mois'], $regle['jour'], $regle['echeance']);
    }

    /**
     * Règle du calendrier (valeurs du formulaire) : premier mois de loyer, jour d'échéance et mois de l'échéance
     * (`mois_loyer` : le mois du loyer, `mois_suivant` : le mois qui suit). Déduite du calendrier enregistré, sinon
     * proposée : septembre de l'année de début de la campagne, le 10 du mois du loyer.
     *
     * @return array{premier_mois: string, jour: int, echeance: string}
     */
    public function regle(Campagne $campagne): array
    {
        if ($enregistre = $this->de($campagne)) {
            return [
                'premier_mois' => $enregistre[0]['mois'],
                // Jour saisi : le plus grand jour des échéances (les mois courts sont ramenés à leur dernier jour).
                'jour' => max(array_map(fn (array $entree) => (int) substr($entree['echeance'], 8, 2), $enregistre)),
                'echeance' => substr($enregistre[0]['echeance'], 0, 7) === $enregistre[0]['mois'] ? self::MOIS_LOYER : self::MOIS_SUIVANT,
            ];
        }
        $annee = (int) ($campagne->annee_debut ?: $campagne->periode_debut?->year ?: now()->year);

        return ['premier_mois' => sprintf('%04d-%02d', $annee, self::MOIS_DEBUT_PROPOSE), 'jour' => self::JOUR_ECHEANCE, 'echeance' => self::MOIS_LOYER];
    }

    /** Échéance le jour J du mois du loyer. */
    public const MOIS_LOYER = 'mois_loyer';

    /** Échéance le jour J du mois qui suit le mois du loyer. */
    public const MOIS_SUIVANT = 'mois_suivant';

    /**
     * Les 10 mois consécutifs à partir de `$premier` (`aaaa-mm`), échéance le `$jour` du mois du loyer ou du mois suivant
     * (ramené au dernier jour pour les mois plus courts).
     *
     * @return list<array{mois: string, echeance: string}>
     */
    public static function construire(string $premier, int $jour, string $position): array
    {
        $debut = CarbonImmutable::createFromFormat('!Y-m', $premier);

        return array_map(function (int $rang) use ($debut, $jour, $position) {
            $mois = $debut->addMonths($rang);
            $echeance = $position === self::MOIS_SUIVANT ? $mois->addMonth() : $mois;

            return [
                'mois' => $mois->format('Y-m'),
                'echeance' => $echeance->setDay(min($jour, $echeance->daysInMonth))->format('Y-m-d'),
            ];
        }, range(0, self::NOMBRE_MOIS - 1));
    }

    /**
     * Enregistre le calendrier : 10 mois consécutifs à partir du premier mois de loyer, échéance le `$jour` du mois du
     * loyer ou du mois suivant ; reporte les nouvelles échéances sur les loyers non soldés de la campagne. Les mois dont
     * un loyer est déjà payé doivent rester dans les 10 mois.
     *
     * @throws ValidationException
     */
    public function enregistrer(Campagne $campagne, string $premierMois, int $jour, string $position): void
    {
        $erreurs = [];
        if (! self::cleValide($premierMois)) {
            $erreurs['premier_mois'] = 'Choisissez le premier mois de loyer.';
        }
        if ($jour < 1 || $jour > 31) {
            $erreurs['jour'] = 'Le jour d’échéance est compris entre 1 et 31.';
        }
        if (! in_array($position, [self::MOIS_LOYER, self::MOIS_SUIVANT], true)) {
            $erreurs['echeance'] = 'Choisissez le mois de l’échéance.';
        }
        if ($erreurs) {
            throw ValidationException::withMessages($erreurs);
        }

        $liste = self::construire($premierMois, $jour, $position);
        $retires = array_values(array_diff($this->moisPayes($campagne), array_column($liste, 'mois')));
        if ($retires) {
            sort($retires);
            throw ValidationException::withMessages([
                'premier_mois' => sprintf(
                    'Des loyers sont déjà payés pour %s : ce%s mois doi%s rester dans les 10 mois du calendrier.',
                    implode(', ', array_map(self::libelle(...), $retires)),
                    count($retires) > 1 ? 's' : '',
                    count($retires) > 1 ? 'vent' : 't',
                ),
            ]);
        }

        DB::transaction(function () use ($campagne, $liste) {
            $campagne->forceFill(['calendrier_loyer' => $liste])->save();

            // Loyers non soldés de la campagne : la date limite suit la nouvelle échéance de leur mois.
            $assignations = AssignationChambre::query()->select('id_assignation')->where('campagne_id', $campagne->getKey());
            foreach ($liste as $entree) {
                [$annee, $mois] = array_map('intval', explode('-', $entree['mois']));
                Loyer::query()->whereIn('assignation_id', $assignations)
                    ->where('annee_facturation', $annee)->where('mois_facturation', $mois)
                    ->where('solde_restant', '>', 0)
                    ->update(['date_limit_paiement' => Carbon::parse($entree['echeance'])->setTime(23, 59, 59)]);
            }
        });
    }

    /**
     * Mois (`aaaa-mm`) dont un loyer de la campagne est déjà payé, même en partie : loyers facturés réglés et mois
     * couverts par un paiement validé de loyer ou d'avance.
     *
     * @return list<string>
     */
    public function moisPayes(Campagne $campagne): array
    {
        $assignations = AssignationChambre::query()->select('id_assignation')->where('campagne_id', $campagne->getKey());
        $factures = Loyer::query()->whereIn('assignation_id', $assignations)->where('total_paye', '>', 0)
            ->get(['mois_facturation', 'annee_facturation'])
            ->map(fn (Loyer $loyer) => sprintf('%04d-%02d', $loyer->annee_facturation, $loyer->mois_facturation));
        $couverts = Paiement::query()->deCampagne((string) $campagne->getKey())->where('statut_paiement', 'valide')
            ->where(fn ($q) => $q->whereIn('type_paiement', PaiementService::TYPES_LOYER)->orWhereNull('type_paiement'))
            ->get(['mois'])
            ->flatMap(fn (Paiement $paiement) => self::clesMois($paiement->mois));

        return $factures->merge($couverts)->unique()->sort()->values()->all();
    }

    /** Campagne de l'étudiant : celle de sa dernière attribution confirmée, sinon celle de sa dernière demande. */
    public function campagneEtudiant(string $etudiantId): ?Campagne
    {
        $id = AssignationChambre::query()->where('etudiant_id', $etudiantId)->where('statut', 'confirmee')
            ->latest('created_at')->value('campagne_id')
            ?? DemandeEtudiant::query()->where('etudiant_id', $etudiantId)->latest('created_at')->value('campagne_id');

        return $id ? Campagne::find($id) : null;
    }

    /** Calendrier de la campagne de l'étudiant (`[]` sans calendrier configuré). */
    public function pourEtudiant(string $etudiantId): array
    {
        return $this->de($this->campagneEtudiant($etudiantId));
    }

    /** Date limite de paiement du loyer d'un mois : échéance du calendrier, sinon le 10 du mois (23:59:59). */
    public function echeance(array $calendrier, int $mois, int $annee): Carbon
    {
        $entree = collect($calendrier)->firstWhere('mois', sprintf('%04d-%02d', $annee, $mois));

        return $entree
            ? Carbon::parse($entree['echeance'])->setTime(23, 59, 59)
            : Carbon::create($annee, $mois, self::JOUR_ECHEANCE, 23, 59, 59);
    }

    /** Mois du calendrier qui suit `aaaa-mm` ; null s'il n'en reste aucun. */
    public function moisApres(array $calendrier, string $cle): ?string
    {
        return collect($calendrier)->pluck('mois')->first(fn (string $mois) => $mois > $cle);
    }

    /** Premier mois du calendrier à partir de `aaaa-mm` (inclus) ; null s'il n'en reste aucun. */
    public function moisDepuis(array $calendrier, string $cle): ?string
    {
        return collect($calendrier)->pluck('mois')->first(fn (string $mois) => $mois >= $cle);
    }

    /**
     * Mois proposés au paiement (`mm-aaaa`) à partir du prochain mois à payer : les mois restants du calendrier de la
     * campagne, sinon `$nombre` mois consécutifs.
     *
     * @return list<string>
     */
    public function moisProposes(?Campagne $campagne, string $prochain, int $nombre): array
    {
        if (! preg_match('/^(\d{2})-(\d{4})$/', $prochain, $p)) {
            return [];
        }
        $depart = "{$p[2]}-{$p[1]}";
        if ($calendrier = $this->de($campagne)) {
            return collect($calendrier)->pluck('mois')->filter(fn (string $mois) => $mois >= $depart)
                ->map(fn (string $mois) => substr($mois, 5, 2).'-'.substr($mois, 0, 4))->values()->all();
        }
        $debut = CarbonImmutable::create((int) $p[2], (int) $p[1], 1);

        return collect(range(0, max(0, $nombre - 1)))->map(fn (int $rang) => $debut->addMonths($rang)->format('m-Y'))->all();
    }

    /** `aaaa-mm` → « octobre 2026 ». */
    public static function libelle(string $cle): string
    {
        [$annee, $mois] = array_map('intval', explode('-', $cle));

        return (self::MOIS[$mois] ?? $cle).' '.$annee;
    }

    /** `paiements.mois` (liste de « mm-aaaa ») → clés « aaaa-mm » des mois reconnus. */
    public static function clesMois($mois): array
    {
        $liste = is_array($mois) ? $mois : (is_string($mois) && $mois !== '' ? (json_decode($mois, true) ?? [$mois]) : []);

        return collect(is_array($liste) ? $liste : [$liste])
            ->map(fn ($m) => preg_match('/^(\d{1,2})-(\d{4})$/', (string) $m, $p) && (int) $p[1] >= 1 && (int) $p[1] <= 12
                ? sprintf('%04d-%02d', $p[2], $p[1])
                : null)
            ->filter()->values()->all();
    }

    private static function cleValide($cle): bool
    {
        return is_string($cle) && preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $cle) === 1;
    }

    private static function dateValide($date): bool
    {
        if (! is_string($date) || ! preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $date, $p)) {
            return false;
        }

        return checkdate((int) $p[2], (int) $p[3], (int) $p[1]);
    }
}
