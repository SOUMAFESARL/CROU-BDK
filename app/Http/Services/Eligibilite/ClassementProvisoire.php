<?php

namespace App\Http\Services\Eligibilite;

use App\Http\Requests\DemandeHebergementRequest;
use App\Http\Services\NoteConduite;
use App\Models\Campagne;
use App\Models\DemandeEtudiant;
use App\Models\Etudiant;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Classement provisoire des demandes recevables d'une campagne, par volet, selon les critères de classement de la
 * campagne (version en vigueur, sinon modèle CROU ; docs/notes/eligibilite-criteres-campagne.md) :
 *
 * - **admission** : boursiers devant les non-boursiers si la priorité est retenue (paramétrage général, cochée par
 *   défaut) ; mérite du public de l'étudiant, en % du maximum (note du bac sur 400 pour un nouveau bachelier,
 *   crédits validés sur 60 de la Licence 2 au doctorat), décroissant ; puis le départage dans l'ordre paramétré
 *   (bac obtenu hors de la ville de référence, puis le plus jeune) ;
 * - **réadmission** : catégorie (C1 écoles sur concours au-delà du seuil de la filière, puis C6 places restantes),
 *   boursiers devant les non-boursiers si la priorité est retenue (paramétrage général), mérite de la catégorie (C1 :
 *   % des crédits puis moyenne ; C6 : moyenne), puis le départage paramétré (le plus jeune, comportement = note de conduite).
 *   Les catégories C2 à C5 (rang du PV, clubs, cas sociaux, comité) demandent des listes qui ne sont pas encore
 *   saisies : leurs candidats figurent en C6. Le départage « excellence » n'a pas encore de donnée et est ignoré.
 *
 * En dernier recours, la demande déposée la première passe devant. Le classement est indicatif : il ne change pas
 * l'état du dossier (`DossierWorkflow`). La longueur de la liste de chaque volet est limitée (`capaciteListe`) sur le total
 * des places du volet, sans répartition par cité.
 */
class ClassementProvisoire
{
    public function __construct(private CriteresCampagne $criteres, private NoteConduite $conduite) {}

    /**
     * @param  iterable<Etudiant>  $etudiants  demandes recevables, avec `derniereDemande` (campagne, niveaux) chargée
     * @param  array<string, string>  $volets  volet par étudiant (`CriteresCampagne::voletsDemandes`)
     * @return array{admission: array, readmission: array} lignes classées par volet (rang à partir de 1)
     */
    public function classer(iterable $etudiants, Campagne $campagne, array $volets): array
    {
        $candidats = collect($etudiants)
            ->filter(fn (Etudiant $etudiant) => $etudiant->derniereDemande && (string) $etudiant->derniereDemande->campagne_id === (string) $campagne->getKey());
        $general = $this->regles($campagne, 'general');
        $boursiers = (bool) ($general['priorite_boursiers'] ?? true);

        $resultat = [];
        foreach (array_keys(CriteresCampagne::VOLETS) as $volet) {
            $regles = $this->regles($campagne, $volet);
            $liste = $candidats->filter(fn (Etudiant $etudiant) => ($volets[(string) $etudiant->getKey()] ?? 'admission') === $volet);
            $lignes = $volet === 'admission' ? $this->admission($liste, $regles, $boursiers) : $this->readmission($liste, $regles, $general, $boursiers);
            $resultat[$volet] = $lignes->values()->map(fn (array $ligne, int $index) => ['rang' => $index + 1] + $ligne)->all();
        }

        return $resultat;
    }

    /**
     * Nombre de lignes de la liste provisoire d'un volet : places du volet (critères en vigueur, toutes cités) ×
     * coefficient de la liste élargie ; null si aucune place n'est renseignée (liste non limitée).
     */
    public function capaciteListe(Campagne $campagne, string $volet): ?int
    {
        $places = $this->places($campagne, $volet);
        if ($places === null) {
            return null;
        }
        $regles = $this->regles($campagne, $volet);

        return $places * max(1, (int) ($regles['classement']['k_liste_elargie'] ?? 1));
    }

    /** Places du volet (critères en vigueur, toutes cités) ; null si aucune place n'est renseignée. */
    public function places(Campagne $campagne, string $volet): ?int
    {
        $vigueur = Schema::hasTable('campagne_regles') ? $this->criteres->enVigueur($campagne, $volet) : null;
        $places = collect($vigueur?->places ?? [])->pluck('places')->filter(fn ($nombre) => $nombre !== null && $nombre !== '');

        return $places->isEmpty() ? null : (int) $places->sum();
    }

    /** Admission : boursiers (si prioritaires), mérite (en % du maximum), puis départage paramétré. */
    private function admission(Collection $etudiants, array $regles, bool $boursiers): Collection
    {
        $departage = $regles['classement']['departage'] ?? CriteresCampagne::DEPARTAGE_DEFAUT['admission'];
        $reference = $this->normaliser($regles['admission']['ville_bac'] ?? '');

        return $etudiants->map(function (Etudiant $etudiant) use ($departage, $reference, $boursiers) {
            $demande = $etudiant->derniereDemande;
            $merite = $this->meriteAdmission($demande);
            $ville = trim((string) $demande->ville_bac);
            $horsVille = $ville !== '' && $reference !== '' && $this->normaliser($ville) !== $reference;
            $boursier = (bool) $demande->boursier;
            $cles = [
                $boursiers ? ($boursier ? 0 : 1) : 0,
                $merite === null ? INF : -$merite['pourcentage'],
            ];
            foreach ($departage as $critere) {
                $cles[] = match ($critere) {
                    'hors_ville_bac' => $horsVille ? 0 : 1,
                    'age_plus_jeune' => $this->cleAge($etudiant),
                    default => 0,
                };
            }
            $cles[] = $this->cleDepot($demande);

            return ['etudiant' => $etudiant, 'boursier' => $boursier, 'merite' => $merite, 'ville_bac' => $ville !== '' ? $ville : null, 'hors_ville' => $horsVille, 'cles' => $cles];
        })->sort(fn (array $a, array $b) => $a['cles'] <=> $b['cles'])
            ->map(fn (array $ligne) => collect($ligne)->except('cles')->all());
    }

    /** Réadmission : catégorie, boursiers (si prioritaires), mérite de la catégorie, puis départage paramétré. */
    private function readmission(Collection $etudiants, array $regles, array $general, bool $boursiers): Collection
    {
        $classement = $regles['classement'] ?? [];
        $departage = $classement['departage'] ?? CriteresCampagne::DEPARTAGE_DEFAUT['readmission'];
        $categories = $this->criteres->categories($general, $regles);
        $filieres = collect($regles['readmission']['credits']['filieres'] ?? [])->keyBy(fn ($ligne) => (string) ($ligne['filiere_id'] ?? ''));
        $seuilDefaut = (float) ($regles['readmission']['credits']['seuil_defaut'] ?? 100);
        $conduite = in_array('comportement', $departage, true) && Schema::hasTable('sanctions');

        return $etudiants->map(function (Etudiant $etudiant) use ($boursiers, $departage, $categories, $filieres, $seuilDefaut, $conduite) {
            $demande = $etudiant->derniereDemande;
            $credits = is_numeric($demande->credit_valider) ? DemandeEtudiant::pourcentageCredits($demande->credit_valider) : null;
            $moyenne = is_numeric($demande->moyenne) ? (float) $demande->moyenne : null;
            $filiere = $filieres->get((string) $demande->filiere_code);
            $concours = ($categories['C1']['actif'] ?? false) && ! empty($filiere['concours']) && $credits !== null
                && $credits >= (float) ($filiere['seuil'] ?? $seuilDefaut);
            $categorie = $concours ? 'C1' : 'C6';
            $boursier = (bool) $demande->boursier;

            // Clés de même longueur pour toutes les lignes (PHP compare d'abord la taille des tableaux).
            $cles = [
                $categorie === 'C1' ? 0 : 1,
                $boursiers ? ($boursier ? 0 : 1) : 0,
                $categorie === 'C1' ? -$credits : 0,
                $moyenne === null ? INF : -$moyenne,
            ];
            foreach ($departage as $critere) {
                $cles[] = match ($critere) {
                    'age_plus_jeune' => $this->cleAge($etudiant),
                    'comportement' => $conduite ? -$this->conduite->note((string) $etudiant->getKey()) : 0,
                    default => 0,
                };
            }
            $cles[] = $this->cleDepot($demande);

            return [
                'etudiant' => $etudiant,
                'categorie' => ['code' => $categorie, 'libelle' => CriteresCampagne::CATEGORIES[$categorie]],
                'boursier' => $boursier,
                'moyenne' => $moyenne,
                'credits' => $credits,
                'cles' => $cles,
            ];
        })->sort(fn (array $a, array $b) => $a['cles'] <=> $b['cles'])
            ->map(fn (array $ligne) => collect($ligne)->except('cles')->all());
    }

    /** Mérite du public de l'étudiant : note du bac (sur 400) pour un nouveau bachelier, crédits validés (sur 60) sinon. */
    private function meriteAdmission(DemandeEtudiant $demande): ?array
    {
        $precedent = $demande->niveauEtudiantPrecedent;
        if ($precedent === null) {
            return null;
        }
        if (CriteresCampagne::niveauSansAgeMax($precedent->name, $precedent->abreviation ?? null)) {
            if (! is_numeric($demande->moyenne)) {
                return null;
            }
            $max = DemandeHebergementRequest::NOTE_BAC_MAX;
            $note = round((float) $demande->moyenne * $max / 20, 2);

            return ['libelle' => 'Note du bac', 'valeur' => $note, 'max' => $max, 'pourcentage' => round($note / $max * 100, 2)];
        }
        if (! is_numeric($demande->credit_valider)) {
            return null;
        }
        $credits = (float) $demande->credit_valider;

        return ['libelle' => 'Crédits validés', 'valeur' => $credits, 'max' => DemandeEtudiant::CREDITS_PAR_AN, 'pourcentage' => DemandeEtudiant::pourcentageCredits($credits)];
    }

    /** Règles lues comme pour la conformité : version en vigueur, sinon modèle CROU (un brouillon n'est jamais lu). */
    private function regles(Campagne $campagne, string $jeu): array
    {
        $vigueur = Schema::hasTable('campagne_regles') ? $this->criteres->enVigueur($campagne, $jeu) : null;

        return $this->criteres->completer($jeu, $vigueur?->parametres ?? [], $campagne);
    }

    /** Le plus jeune d'abord ; sans date de naissance, en dernier. */
    private function cleAge(Etudiant $etudiant): float
    {
        return $etudiant->date_naissance ? -Carbon::parse($etudiant->date_naissance)->getTimestamp() : INF;
    }

    /** Demande déposée la première d'abord. */
    private function cleDepot(DemandeEtudiant $demande): float
    {
        return $demande->created_at ? (float) Carbon::parse($demande->created_at)->getTimestamp() : INF;
    }

    private function normaliser(?string $ville): string
    {
        return Str::lower(Str::ascii(trim((string) $ville)));
    }
}
