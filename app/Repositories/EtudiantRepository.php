<?php
namespace App\Repositories;

use App\Models\Etudiant;
use App\Repositories\Interfaces\EtudiantRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Http\Services\DemandeService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection; 

class EtudiantRepository implements EtudiantRepositoryInterface
{
    protected array $allowedSorts = [
        'date_inscription',
        'name',
        
        'matricule',
        'email',
        'telephone1',
        'statut',
        'created_at',
        'updated_at',
    ];

     /**
     * Récupérer tous les étudiants avec tri dynamique
     */
    public function getAll(?string $sortBy = null, ?string $sortDirection = 'asc'): Collection
    {
        $query = Etudiant::withDefaultRelations();
      
        // Appliquer le tri dynamique
        $query = $this->applySorting($query, $sortBy, $sortDirection);
         
        return $query->get();
    }

    /**
     * Applique le tri sur la requête
     */
    private function applySorting(Builder $query, ?string $sortBy, ?string $sortDirection): Builder
    {
        // Si aucun tri spécifié, utiliser le tri par défaut
        if (!$sortBy || !in_array($sortBy, $this->allowedSorts)) {
            return $query->orderBy('date_inscription', 'DESC');
        }
        
        // Vérifier la direction
        $direction = in_array(strtolower($sortDirection), ['asc', 'desc']) 
            ? strtolower($sortDirection) 
            : 'asc';
        
        return $query->orderBy($sortBy, $direction);
    }

    public function create(array $data): Etudiant
    {
        return DB::transaction(function () use ($data) {

        if (app(\App\Http\Services\StudentIdentityResolver::class)->find($data['telephone1'] ?? null, $data['numero_piece'] ?? null)) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'telephone'=>'Un compte existe déjà. Connectez-vous pour déposer une demande depuis votre espace étudiant.',
            ]);
        }
        $etudiant =    Etudiant::firstOrCreate($data,[
                'date_inscription' => now(),
                'statut_inscription' => 'demande',
                'is_validation' => false,
                'is_rejet' => false,
            ]);
            return $etudiant;

        });

      

    }

    private function uploadFile($file, $directory): string
    {
        $path = $file->store($directory, 'public');
        return $path;
    }

    public function update(string $id, array $data): Etudiant
    {
      // dd($data,'cxzvhjkfdhxlcvmn ');
        return DB::transaction(function () use ($id, $data) {
            $etudiant = Etudiant::where('id_etudiant',$id)->first();
            //$data['updated_by'] = auth()->id();
            $etudiant->update($data);
            return $etudiant->fresh();
        });
    }

    public function find(string $id): ?Etudiant
    {
        return Etudiant::where('id_etudiant',$id)->first();
    }

    public function delete(int $id): bool
    {
        return DB::transaction(function () use ($id) {
            $etudiant = Etudiant::findOrFail($id);
            $etudiant->deleted_by = auth()->id();
            $etudiant->save();
            return $etudiant->delete();
        });
    }



    public function findWithRelations(string $id): ?Etudiant
    {

        return Etudiant::withDefaultRelations()->where('id_etudiant',$id)->first();

    }

    public function getAllWithFilters(?string $etat = null): Collection
    {
        $query = Etudiant::withDefaultRelations()->orderBy('date_inscription', 'DESC');

        switch ($etat) {
            case 'residents':
                $query->where('statut_inscription', 'residents');
                break;
            case 'en_attente':
                $query->where('statut_inscription', 'en_attente');
                break;
            case 'nouvelles_demandes':
                $query->where('statut_inscription', 'demande');

                break;
            case 'en_verification':
                $query->where('statut_inscription', 'En_verification');

                break;
            case 'annuler':
                $query->where('statut_inscription', 'annuler');
                break;
            default:
                //dd($query->get());
                $query->where('statut_inscription', 'residents');
                break;

        }
            //dd($query->get());
        return $query->get();
    }

    public function countResidents(): int
    {
        return Etudiant::where('statut_inscription', 'residents')->count();
    }

    public function countNouvellesDemandes(): int
    {
        return Etudiant::where('statut_inscription', 'demande')->count();
    }

    public function countEnAttente(): int
    {
        return Etudiant::where('statut_inscription', 'en_attente')->count();
    }

    public function countAnnulees(): int
    {
        return Etudiant::where('statut_inscription', 'annuler')->count();
    }
    public function countVerification(): int
    {
        return Etudiant::where('statut_inscription', 'En_verification')->count();
    }

    public function getStats(): array
    {
        $counts = Etudiant::selectRaw('LOWER(statut_inscription) as state, COUNT(*) as aggregate')
            ->groupByRaw('LOWER(statut_inscription)')->pluck('aggregate','state');
        return [
            'residents'=>(int)($counts['residents'] ?? 0),
            'nouvelles_demandes'=>(int)($counts['demande'] ?? 0),
            'en_attente'=>(int)($counts['en_attente'] ?? 0),
            'annulees'=>(int)($counts['annuler'] ?? 0),
            'en_verification'=>(int)($counts['en_verification'] ?? 0),
            'total'=>(int)$counts->sum(),
        ];
    }

    /**
     * `$recherche` : nom, matricule, téléphone, numéro de demande ou numéro de chambre (affectation confirmée).
     * `$etatDossier` : état (`Demande.etat_dossier`) de la dernière demande de l'étudiant.
     */
    public function getAdmissionsPage(?string $etat = null, int $parPage = 25, ?string $recherche = null, ?string $etatDossier = null): \Illuminate\Pagination\LengthAwarePaginator
    {
        $recherche = trim((string) $recherche);
        $etatDossier = trim((string) $etatDossier);
        $status = match($etat) {
            'nouvelles_demandes'=>'demande', 'en_attente'=>'en_attente',
            'en_verification'=>'en_verification', 'annuler'=>'annuler', default=>'residents',
        };
        return Etudiant::with(['region','ville','derniereDemande'])->withCount('demandes')
            ->whereRaw('LOWER(statut_inscription) = ?',[$status])
            ->when($recherche !== '', function ($query) use ($recherche) {
                $motif = '%'.addcslashes($recherche, '%_\\').'%';
                $query->where(fn ($q) => $q->where('name', 'like', $motif)
                    ->orWhere('matricule', 'like', $motif)
                    ->orWhere('telephone1', 'like', $motif)
                    ->orWhereHas('demandes', fn ($d) => $d->where('numero_demande', 'like', $motif))
                    ->orWhereHas('assignationsChambre', fn ($a) => $a->where('statut', 'confirmee')
                        ->whereHas('chambre', fn ($c) => $c->where('numero_chambre', 'like', $motif))));
            })
            ->when($etatDossier !== '', fn ($query) => $query->whereHas('derniereDemande', fn ($d) => $d->where('etat_dossier', $etatDossier)))
            ->orderByDesc('date_inscription')->orderByDesc('id_etudiant')
            ->paginate($parPage)->withQueryString();
    }

    public function verifierEligibilite(mixed $demande, mixed $campagne, bool $ignorerDocuments = false): array
{
    $regles = [];
    $discipline = app(\App\Http\Services\DisciplinaryImpactService::class)->statut($demande->getKey());
    if (in_array($discipline, ['expulsion', 'suspension'], true)) {
        return ['regles' => [['label' => 'Absence de sanction disciplinaire bloquante', 'ok' => false]],
            'score_global' => 0, 'conforme_count' => 0, 'total_regles' => 1,
            'eligible' => false, 'documents_ignores' => $ignorerDocuments];
    }

    $regles[] = $this->regleAge($demande, $campagne);
    $regles[] = $this->regleMoyenne($demande, $campagne);
    $regles[] = $this->regleCreditValide($demande, $campagne);
    $regles[] = $this->regleDistance($demande, $campagne);
    $regles[] = $this->regleBoursier($demande, $campagne);
    $regles[] = $this->regleRedoublement($demande, $campagne);
    $regles[] = $this->reglePmr($demande, $campagne);
    $regles[] = $this->regleInternational($demande, $campagne);
    $regles[] = $this->regleNouveauBachelier($demande, $campagne);
    $regles[] = $this->regleReadmission($demande, $campagne);
    $regles[] = $this->regleDocuments($demande, $campagne); // toujours ajoutée pour l'affichage

    // Supprimer les règles nulles (non applicables / campagne non configurée)
    $regles = array_filter($regles);
    $regles = array_values($regles);

    // Règles utilisées pour le calcul d'éligibilité (documents exclus si demandé)
    $reglesEligibilite = $ignorerDocuments
        ? array_values(array_filter($regles, fn($r) => $r['label'] !== 'Documents validés'))
        : $regles;

    $totalRegles   = count($reglesEligibilite);
    $conformeCount = collect($reglesEligibilite)->where('ok', true)->count();
    $scoreGlobal   = $totalRegles > 0 ? round(($conformeCount / $totalRegles) * 100) : 0;
//dd($regles, $reglesEligibilite, $totalRegles, $conformeCount, $scoreGlobal);
    return [
        'regles'            => $regles,           // liste complète, documents inclus, pour affichage
        'score_global'      => $scoreGlobal,
        'conforme_count'    => $conformeCount,
        'total_regles'      => $totalRegles,
        'eligible'          => $conformeCount === $totalRegles && $totalRegles > 0,
        'documents_ignores' => $ignorerDocuments,
    ];
}

    /* ──────────────────────────────────────────────────────────────
     |  RÈGLE 1 – Âge maximum selon le niveau
     ────────────────────────────────────────────────────────────── */
    private function regleAge(mixed $demande, mixed $campagne): ?array
    {
        if (is_array($campagne)) {
            $campagne = $campagne['data']['campagnes']->first();
        }
        $abreviation = optional(optional($demande->derniereDemande)->niveauEtudiantActuel)->abreviation ?? '';
        $niveauKey   = 'age_' . strtoupper($abreviation) . '_max';
        $ageMax      = $campagne?->{$niveauKey} ?? null;

        if ($ageMax === null) {
            return null;
        }

        $age       = $demande->date_naissance?->age;
        $niveauNom = strtoupper(optional(optional($demande->derniereDemande)->niveauEtudiantActuel)->name ?? '?');

        if ($age === null) {
            return $this->regle(
                label    : "Âge maximum (niveau {$niveauNom})",
                campagne : '≤ ' . $ageMax . ' ans',
                etudiant : 'Date de naissance non renseignée',
                ok       : false,
            );
        }

        $conforme = $age <= $ageMax;

        return $this->regle(
            label    : "Âge maximum (niveau {$niveauNom})",
            campagne : '≤ ' . $ageMax . ' ans',
            etudiant : $age . ' ans',
            ok       : $conforme,
        );
    }

    /* ──────────────────────────────────────────────────────────────
     |  RÈGLE 2 – Moyenne minimale
     ────────────────────────────────────────────────────────────── */
    private function regleMoyenne(mixed $demande, mixed $campagnes): ?array
    {
        $campagne= $campagnes['data']['campagnes']->first();

        if ($campagne?->moyenne_min === null) {
            return null;
        }

        $score = optional($demande->derniereDemande)->moyenne ?? null;

        if ($score === null) {
            return $this->regle(
                label    : 'Moyenne',
                campagne : '≥ ' . $campagne->moyenne_min . '/20',
                etudiant : 'Non renseignée',
                ok       : false,
            );
        }

        $conforme = $score >= $campagne->moyenne_min;

        return $this->regle(
            label    : 'Moyenne',
            campagne : '≥ ' . $campagne->moyenne_min . '/20',
            etudiant : $score . '/20',
            ok       : $conforme,
        );
    }

    /* ──────────────────────────────────────────────────────────────
     |  RÈGLE 3 – Crédits validés (hors L1)
     ────────────────────────────────────────────────────────────── */
    private function regleCreditValide(mixed $demande, mixed $campagnes): ?array
    {
        $campagne= $campagnes['data']['campagnes']->first();

        $abreviation = optional(optional($demande->derniereDemande)->niveauEtudiantActuel)->abreviation;

        if ($campagne?->pourcentage_validate === null || $abreviation === 'L1') {
            return null;
        }

        $score = optional($demande->derniereDemande)->credit_valider ?? null;

        if ($score === null) {
            return $this->regle(
                label    : 'Crédits validés',
                campagne : '≥ ' . $campagne->pourcentage_validate . ' %',
                etudiant : 'Non renseigné',
                ok       : false,
            );
        }

        // credit_valider est un nombre de crédits sur 60 ; le seuil de la campagne reste un pourcentage.
        $pourcentage = \App\Models\DemandeEtudiant::pourcentageCredits($score);
        $conforme = $pourcentage >= $campagne->pourcentage_validate;

        return $this->regle(
            label    : 'Crédits validés',
            campagne : '≥ ' . $campagne->pourcentage_validate . ' %',
            etudiant : $score . '/' . \App\Models\DemandeEtudiant::CREDITS_PAR_AN . ' crédits (' . round($pourcentage) . ' %)',
            ok       : $conforme,
        );
    }

    /* ──────────────────────────────────────────────────────────────
     |  RÈGLE 4 – Distance domicile ↔ campus
     ────────────────────────────────────────────────────────────── */
    private function regleDistance(mixed $demande, mixed $campagnes): ?array
    {
        $campagne= $campagnes['data']['campagnes']->first();
        if ($campagne?->distance_min === null) {
            return null;
        }

        $distance = $demande->distance ?? null;

        if ($distance === null) {
            return $this->regle(
                label    : 'Distance domicile – campus',
                campagne : '≥ ' . $campagne->distance_min . ' km',
                etudiant : 'Non renseignée',
                ok       : false,
            );
        }

        $conforme = $distance >= $campagne->distance_min;

        return $this->regle(
            label    : 'Distance domicile – campus',
            campagne : '≥ ' . $campagne->distance_min . ' km',
            etudiant : $distance . ' km',
            ok       : $conforme,
        );
    }

    /* ──────────────────────────────────────────────────────────────
     |  RÈGLE 5 – Statut boursier (toujours accepté)
     ────────────────────────────────────────────────────────────── */
    private function regleBoursier(mixed $demande, mixed $campagnes): ?array
    {
        $campagne= $campagnes['data']['campagnes']->first();
        if ($campagne === null) {
            return null;
        }

        return $this->regle(
            label    : 'Boursier',
            campagne : 'Acceptés',
            etudiant : $demande->boursier ? 'Oui' : 'Non',
            ok       : true,  // Toujours OK
        );
    }

    /* ──────────────────────────────────────────────────────────────
     |  RÈGLE 6 – Redoublement
     ────────────────────────────────────────────────────────────── */
    private function regleRedoublement(mixed $demande, mixed $campagnes): ?array
    {
        $campagne= $campagnes['data']['campagnes']->first();
        if ($campagne?->redoublement === null) {
            return null;
        }

        $autoriseRedoublement = (bool) $campagne->redoublement;
        $estRedoublant        = (bool) ($demande->redoublant ?? false);

        $conforme = $autoriseRedoublement || !$estRedoublant;

        return $this->regle(
            label    : 'Redoublement',
            campagne : $autoriseRedoublement ? 'Autorisé' : 'Non autorisé',
            etudiant : $estRedoublant ? 'Redoublant' : 'Non redoublant',
            ok       : $conforme,
        );
    }

    /* ──────────────────────────────────────────────────────────────
     |  RÈGLE 7 – PMR (Personne à Mobilité Réduite)
     ────────────────────────────────────────────────────────────── */
    private function reglePmr(mixed $demande, mixed $campagnes): ?array
    {
        $campagne= $campagnes['data']['campagnes']->first();
        if ($campagne?->pmr === null) {
            return null;
        }

        $pmrAutorise = (bool) $campagne->pmr;
        $estPmr      = (bool) ($demande->pmr ?? false);
        $conforme    = !$estPmr || $pmrAutorise;

        return $this->regle(
            label    : 'PMR (Mobilité Réduite)',
            campagne : $pmrAutorise ? 'Accepté' : 'Non accepté',
            etudiant : $estPmr ? 'Oui' : 'Non',
            ok       : $conforme,
        );
    }

    /* ──────────────────────────────────────────────────────────────
     |  RÈGLE 8 – Étudiants internationaux
     ────────────────────────────────────────────────────────────── */
    private function regleInternational(mixed $demande, mixed $campagnes): ?array
    {
        $campagne= $campagnes['data']['campagnes']->first();
        if ($campagne?->internationnaux === null) {
            return null;
        }

        $intlAutorise = (bool) $campagne->internationnaux;
        $nationalite  = strtolower(trim($demande->nationalite ?? ''));
        $estIntl      = filled($nationalite)
            && !in_array($nationalite, ['ivoirienne', 'ivoirien'], strict: true);

        $conforme = !$estIntl || $intlAutorise;

        return $this->regle(
            label    : 'Étudiant international',
            campagne : $intlAutorise ? 'Accepté' : 'Non accepté',
            etudiant : $estIntl ? 'Oui (' . ($demande->nationalite ?? '?') . ')' : 'Non',
            ok       : $conforme,
        );
    }

    /* ──────────────────────────────────────────────────────────────
     |  RÈGLE 9 – Nouveau bachelier
     ────────────────────────────────────────────────────────────── */
    private function regleNouveauBachelier(mixed $demande, mixed $campagnes): ?array
    {
        $campagne= $campagnes['data']['campagnes']->first();
        if ($campagne?->new_bachelier === null) {
            return null;
        }

        $nouveauBachAutorise = (bool) $campagne->new_bachelier;
        $estNouveauBach      = (bool) ($demande->nouveau_bachelier ?? false);
        $conforme            = !$estNouveauBach || $nouveauBachAutorise;

        return $this->regle(
            label    : 'Nouveau bachelier',
            campagne : $nouveauBachAutorise ? 'Accepté' : 'Non accepté',
            etudiant : $estNouveauBach ? 'Oui' : 'Non',
            ok       : $conforme,
        );
    }

    /* ──────────────────────────────────────────────────────────────
     |  RÈGLE 10 – Réadmission
     ────────────────────────────────────────────────────────────── */
    private function regleReadmission(mixed $demande, mixed $campagnes): ?array
    {
        $campagne= $campagnes['data']['campagnes']->first();
        if ($campagne?->readmission === null) {
            return null;
        }

        $readmissionAutorisee = (bool) $campagne->readmission;
        $nbDemandes           = $demande->demandes->count();
        $estReadmission       = $nbDemandes > 1;
        $conforme             = !$estReadmission || $readmissionAutorisee;

        return $this->regle(
            label    : 'Réadmission',
            campagne : $readmissionAutorisee ? 'Autorisée' : 'Non autorisée',
            etudiant : $estReadmission ? "Oui ({$nbDemandes} demande(s))" : 'Non',
            ok       : $conforme,
        );
    }

    /* ──────────────────────────────────────────────────────────────
     |  RÈGLE 11 – Pourcentage de documents validés
     ────────────────────────────────────────────────────────────── */
    private function regleDocuments(mixed $demande, mixed $campagnes): array
    {
        $campagne= $campagnes['data']['campagnes']->first();
        $totalDocs  = count($demande->documents);
        $valideDocs = collect($demande->documents)->where('statut', 'valide')->count();
        $pctDocs    = $totalDocs > 0 ? round(($valideDocs / $totalDocs) * 100) : 0;
        $pctRequis  = $campagne?->pourcentage_validate ?? 100;

        $conforme = $pctDocs >= $pctRequis;

        return $this->regle(
            label    : 'Documents validés',
            campagne : '≥ ' . $pctRequis . ' %',
            etudiant : "{$valideDocs}/{$totalDocs} ({$pctDocs} %)",
            ok       : $conforme,
        );
    }

    /* ──────────────────────────────────────────────────────────────
     |  Helper – Construction d'une ligne de règle (sans badge)
     ────────────────────────────────────────────────────────────── */
    private function regle(
        string $label,
        string $campagne,
        string $etudiant,
        bool   $ok,
    ): array {
        return compact('label', 'campagne', 'etudiant', 'ok');
    }


}
