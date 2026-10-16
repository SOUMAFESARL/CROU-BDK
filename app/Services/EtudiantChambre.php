<?php

namespace App\Services;

use App\Models\AssignationChambre;
use App\Models\Chambre;
use App\Models\Etudiant;
use App\Models\OrdreTravail;
use Illuminate\Support\Collection;

/**
 * Props de la page React « Ma chambre » de l'espace étudiant (Etudiant/MaChambre).
 * Chambre de l'assignation confirmée (active, sinon la prochaine), équipements affectés,
 * colocataires et derniers signalements. Seules les valeurs affichées sont envoyées, déjà formatées.
 */
class EtudiantChambre
{
    /** États des affectations d'équipement (EquipmentAllocationController) : libellé et variante du badge. */
    private const ETATS_EQUIPEMENT = [
        'bon' => ['Bon état', 'success'],
        'moyen' => ['État moyen', 'warning'],
        'defectueux' => ['Défectueux', 'danger'],
    ];

    /** Statuts des ordres de travail (OrdreTravail::STATUTS) : libellé et variante du badge (repris par EtudiantMaintenance). */
    public const STATUTS_SIGNALEMENT = [
        'en_attente' => ['En attente', 'warning'],
        'en_cours' => ['En cours', 'info'],
        'en_attente_pieces' => ['En attente de pièces', 'warning'],
        'termine' => ['Terminé', 'success'],
        'annule' => ['Annulé', 'neutral'],
        'reporte' => ['Reporté', 'neutral'],
    ];

    public function props(Etudiant $etudiant): array
    {
        $assignation = $this->assignation((string) $etudiant->getKey());

        return [
            'chambre' => $assignation?->chambre ? $this->chambre($assignation) : null,
            'categories' => self::categories(),
            'urls' => [
                'signalement' => route('etudiants.store_ot'),
                'tableauDeBord' => route('etudiants.dashboard'),
                'maintenance' => route('etudiants.dashboard', ['section' => 'maintenance']),
                'etatDesLieux' => route('etudiants.dashboard', ['section' => 'etat-lieux']),
                'paiements' => route('etudiants.dashboard', ['section' => 'paiements']),
                'sortie' => route('etudiants.dashboard', ['section' => 'demandes', 'focus' => 'departure']),
                'contrat' => route('etudiants.pdfContrat'),
            ],
        ];
    }

    /** Assignation confirmée en cours, sinon la plus récente confirmée (entrée à venir). */
    public function assignation(string $etudiantId): ?AssignationChambre
    {
        $relations = ['chambre.batiment', 'chambre.cite', 'chambre.campus', 'chambre.typeChambre', 'lit'];

        return AssignationChambre::with($relations)->actives()->where('etudiant_id', $etudiantId)->latest('date_assignation')->first()
            ?? AssignationChambre::with($relations)->where('etudiant_id', $etudiantId)->where('statut', 'confirmee')
                ->where(fn ($q) => $q->whereNull('date_fin')->orWhere('date_fin', '>', now()))
                ->latest('date_assignation')->first();
    }

    private function chambre(AssignationChambre $assignation): array
    {
        /** @var Chambre $chambre */
        $chambre = $assignation->chambre;
        $type = $chambre->typeChambre;
        $superficie = (float) ($chambre->superficie ?: ($type?->superficie ?? 0));
        $colocataires = $this->colocataires($assignation);
        $capacite = (int) ($chambre->capacite_max ?: 1);
        $entree = $assignation->date_assignation;

        return [
            'numero' => $chambre->numero_chambre ?: ($chambre->code_chambre ?: 'Non renseignée'),
            'lit' => $assignation->lit?->numero ? 'Lit '.$assignation->lit->numero : null,
            'batiment' => $chambre->batiment?->name,
            'residence' => $chambre->cite?->name,
            'campus' => $chambre->campus?->name,
            'type' => $type?->name,
            'loyer' => formatSystemAmount($type?->montant ?? $chambre->prix ?? 0),
            'superficie' => $superficie > 0 ? rtrim(rtrim(number_format($superficie, 2, ',', ' '), '0'), ',').' m²' : null,
            'occupation' => ['occupants' => $colocataires->count() + 1, 'capacite' => max($capacite, $colocataires->count() + 1)],
            'orientation' => $chambre->cote_orientation ? ucfirst($chambre->cote_orientation) : null,
            'confort' => $chambre->niveau_confort,
            'pmr' => (bool) $chambre->pmr,
            'description' => $type?->description,
            'entree' => $entree ? formatDateFr($entree->format('Y-m-d'), true) : null,
            'fin' => $assignation->date_fin ? formatDateFr($assignation->date_fin->format('Y-m-d'), true) : null,
            // Entrée future : la chambre est attribuée mais l'étudiant n'y réside pas encore.
            'aVenir' => $entree !== null && $entree->isFuture(),
            'equipements' => $this->equipements($chambre),
            'colocataires' => $colocataires->all(),
            'signalements' => $this->signalements((string) $assignation->etudiant_id),
        ];
    }

    /**
     * Équipements affectés à la chambre (stock, non réintégrés) ; à défaut, liste indicative
     * enregistrée sur la chambre ou son type (sans état ni identifiant).
     *
     * @return list<array{id: ?string, nom: string, quantite: int, etat: ?string, ton: string}>
     */
    public function equipements(Chambre $chambre): array
    {
        $affectations = rescue(fn () => $chambre->equipementAffectations()->with('equipement')->whereNull('returned_at')->get(), collect(), false);
        if ($affectations->isNotEmpty()) {
            return $affectations->map(function ($affectation) {
                [$etat, $ton] = self::ETATS_EQUIPEMENT[$affectation->etat] ?? [ucfirst((string) $affectation->etat), 'neutral'];

                return [
                    'id' => (string) $affectation->equipement_id,
                    'nom' => ucfirst((string) ($affectation->equipement?->name ?: 'Équipement')),
                    'quantite' => (int) $affectation->quantite,
                    'etat' => $etat,
                    'ton' => $ton,
                ];
            })->values()->all();
        }

        $liste = json_decode((string) ($chambre->equipements ?: $chambre->typeChambre?->equipement), true);

        return collect(is_array($liste) ? $liste : [])
            ->map(fn ($item) => trim(is_array($item) ? (string) ($item['name'] ?? '') : (string) $item))
            ->filter()
            ->map(fn (string $nom) => ['id' => null, 'nom' => ucfirst(str_replace('_', ' ', $nom)), 'quantite' => 1, 'etat' => null, 'ton' => 'neutral'])
            ->values()
            ->all();
    }

    /** Autres occupants confirmés et actifs de la même chambre. */
    private function colocataires(AssignationChambre $assignation): Collection
    {
        return AssignationChambre::with(['etudiant', 'lit'])
            ->actives()
            ->where('chambre_id', $assignation->chambre_id)
            ->where('etudiant_id', '!=', $assignation->etudiant_id)
            ->get()
            ->filter(fn ($autre) => $autre->etudiant)
            ->map(fn ($autre) => [
                'nom' => $autre->etudiant->name ?: 'Étudiant',
                'initiales' => EtudiantMenu::initiales($autre->etudiant->name),
                'lit' => $autre->lit?->numero ? 'Lit '.$autre->lit->numero : null,
                'filiere' => $autre->etudiant->faculte,
            ])
            ->values();
    }

    /** Cinq derniers signalements (ordres de travail) déposés par l'étudiant. */
    private function signalements(string $etudiantId): array
    {
        return rescue(fn () => OrdreTravail::query()
            ->where('etudiant_id', $etudiantId)
            ->latest('created_at')
            ->limit(5)
            ->get()
            ->map(function (OrdreTravail $ordre) {
                [$statut, $ton] = self::STATUTS_SIGNALEMENT[$ordre->statut] ?? [ucfirst(str_replace('_', ' ', (string) $ordre->statut)), 'neutral'];

                return [
                    'id' => (string) $ordre->getKey(),
                    'numero' => $ordre->numero_ot,
                    'titre' => $ordre->titre ?: self::libelleCategorie((string) $ordre->categorie),
                    'date' => $ordre->created_at ? formatDateFr($ordre->created_at->format('Y-m-d'), true) : null,
                    'statut' => $statut,
                    'ton' => $ton,
                ];
            })->values()->all(), [], false);
    }

    /** Catégories proposées au signalement (OrdreTravail::CATEGORIES), libellés sans émoji. */
    public static function categories(): array
    {
        return collect(OrdreTravail::CATEGORIES)
            ->map(fn (string $code) => ['code' => $code, 'libelle' => self::libelleCategorie($code)])
            ->values()
            ->all();
    }

    /** Libellé sans émoji de OrdreTravail::CATEGORIES_INTERVENTIONS. */
    public static function libelleCategorie(string $code): string
    {
        $libelle = OrdreTravail::CATEGORIES_INTERVENTIONS[$code] ?? ucfirst($code);

        return trim(preg_replace('/^[^\p{L}]+/u', '', $libelle)) ?: ucfirst($code);
    }
}
