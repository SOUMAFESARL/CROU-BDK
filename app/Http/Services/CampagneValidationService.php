<?php

namespace App\Http\Services;

use App\Models\Campagne;
use App\Models\Etudiant;
use App\Repositories\CampagneRepository;
use Carbon\Carbon;


class CampagneValidationService
{
    protected $campagneRepository;
    public function __construct(
        CampagneRepository $campagneRepository
    ) {
        $this->campagneRepository = $campagneRepository;
    }

    /**
     * Récupère la campagne universitaire de référence.
     *
     * Le nom est conservé pour compatibilité. Une campagne reste la référence
     * de l'année scolaire même après la fermeture de sa période d'admission.
     */
    public function getCampagneActive(): ?Campagne
    {
        static $campagne = null;

        if ($campagne === null) {
            $campagne = $this->campagneRepository->getCurrentCampagne();
        }

        return $campagne;
    }

    /**
     * Valide un étudiant par rapport aux critères de la campagne active
     *
     * @return array{eligible: bool, errors: string[], warnings: string[], campagne_id: string|null}
     */

public function validerEtudiant(Etudiant $etudiant, ?Campagne $campagne = null): array
{
    $campagne ??= $this->getCampagneActive();

    if (!$campagne) {
        return $this->buildResult(false, ['Aucune campagne universitaire configurée.'], [], null, 0);
    }

    $errors   = [];
    $warnings = [];
    $criteres = []; // true = respecté, false = non respecté (compte pour le score)

    // 1. Date limite de dépôt
    $dateOk = Carbon::today()->lte(Carbon::parse($campagne->date_echeance_demande));
    $criteres['date_limite'] = $dateOk;
    if (!$dateOk) {
        $errors[] = sprintf(
            'Date limite de dépôt dépassée (%s).',
            Carbon::parse($campagne->date_echeance_demande)->format('d/m/Y')
        );
    }

    // 2. Moyenne minimale
    $moyenneOk = true;
    if ($campagne->moyenne_min !== null) {
        $moyenneOk = (float) $etudiant->demandes[0]->moyenne >= (float) $campagne->moyenne_min;
        if (!$moyenneOk) {
            $errors[] = sprintf(
                'Moyenne insuffisante : %.2f obtenue / %.2f requise.',
                $etudiant->demandes[0]->moyenne,
                $campagne->moyenne_min
            );
        }
    }
    $criteres['moyenne_min'] = $moyenneOk;

    // 3. Distance minimale
    $distanceOk = true;
    if ($campagne->distance_min !== null) {
        $distanceOk = (float) $etudiant->distance >= (float) $campagne->distance_min;
        if (!$distanceOk) {
            $errors[] = sprintf(
                'Distance insuffisante : %s km / %s km requis.',
                $etudiant->distance,
                $campagne->distance_min
            );
        }
    }
    $criteres['distance_min'] = $distanceOk;

    // 4. Nouveau bachelier
    $nouveauBachelierOk = true;
    if (!$campagne->new_bachelier && $this->estNouveauBachelier($etudiant, $campagne)) {
        $nouveauBachelierOk = false;
        $errors[] = 'Les nouveaux bacheliers ne sont pas acceptés pour cette campagne.';
    }
    $criteres['nouveau_bachelier'] = $nouveauBachelierOk;

    // 5. Ancien bachelier
    $ancienBachelierOk = true;
    if (!$campagne->old_bachelier && !$this->estNouveauBachelier($etudiant, $campagne)) {
        $ancienBachelierOk = false;
        $errors[] = 'Seuls les nouveaux bacheliers sont acceptés pour cette campagne.';
    }
    $criteres['ancien_bachelier'] = $ancienBachelierOk;

    // 6. Redoublement
    $redoublementOk = true;
    if (!$campagne->redoublement && $this->estRedoublant($etudiant)) {
        $redoublementOk = false;
        $errors[] = 'Les redoublants ne sont pas acceptés pour cette campagne.';
    }
    $criteres['redoublement'] = $redoublementOk;

    // 7. Réadmission
    $readmissionOk = true;
    if (!$campagne->readmission && $this->estReadmission($etudiant)) {
        $readmissionOk = false;
        $errors[] = 'Les réadmissions ne sont pas acceptées pour cette campagne.';
    }
    $criteres['readmission'] = $readmissionOk;

    // 8. Étudiants internationaux
    $internationalOk = true;
    if (!$campagne->internationnaux && $this->estInternational($etudiant)) {
        $internationalOk = false;
        $errors[] = 'Les étudiants internationaux ne sont pas acceptés pour cette campagne.';
    }
    $criteres['international'] = $internationalOk;

    // 9. PMR — avertissement uniquement, ne compte PAS dans le score
    if ($etudiant->autiste) {
        $warnings[] = 'Étudiant PMR : vérification complémentaire recommandée.';
    }

    // --- Calcul du score d'éligibilité ---
    $totalCriteres  = count($criteres);
    $criteresValides = count(array_filter($criteres));
    $score = $totalCriteres > 0 ? round(($criteresValides / $totalCriteres) * 100, 2) : 0;

    $seuilAcceptation = 85;
    $eligible = $score >= $seuilAcceptation;
//dd($this->buildResult($eligible, $errors, $warnings, $campagne->id_campagne, $score));
    return $this->buildResult($eligible, $errors, $warnings, $campagne->id_campagne, $score);
}



//     public function validerEtudiant(Etudiant $etudiant, ?Campagne $campagne = null): array
//     { 

//         $campagne ??= $this->getCampagneActive();

//         if (!$campagne) {
//             return $this->buildResult(false, ['Aucune campagne active.']);
//         }

//         $errors   = [];
//         $warnings = [];

//         // 1. Date limite de dépôt
//         if (Carbon::today()->gt(Carbon::parse($campagne->date_echeance_demande))) {

//             $errors[] = sprintf(
//                 'Date limite de dépôt dépassée (%s).',
//                 Carbon::parse($campagne->date_echeance_demande)->format('d/m/Y')
//             );
//         }

//         // 2. Moyenne minimale
//         if ($campagne->moyenne_min !== null && (float)$etudiant->demandes[0]->moyenne < (float)$campagne->moyenne_min) {


//             $errors[] = sprintf(
//                 'Moyenne insuffisante : %.2f obtenue / %.2f requise.',
//                 $etudiant->moyenne,
//                 $campagne->moyenne_min
//             );
//         }
//        // dd($campagne,$etudiant);
//         // 3. Distance minimale
//         if ($campagne->distance_min !== null && (float)$etudiant->distance < (float)$campagne->distance_min) {
//             $errors[] = sprintf(
//                 'Distance insuffisante : %s km / %s km requis.',
//                 $etudiant->distance,
//                 $campagne->distance_min
//             );
//         }

//         // 4. Tranche d'âge
//         $age = Carbon::parse($etudiant->date_naissance)->age;

//         //Revoir cette  partie pour bien appliquer les niveau et les ages.

// //        if ($campagne->age_min !== null && $age < (int)$campagne->age_min) {
// //            $errors[] = sprintf('Âge insuffisant : %d ans (min. %d ans).', $age, $campagne->age_min);
// //        }
// //
// //        if ($campagne->age_max !== null && $age > (int)$campagne->age_max) {
// //            $errors[] = sprintf('Âge dépassé : %d ans (max. %d ans).', $age, $campagne->age_max);
// //        }

//         // 5. Nouveau bachelier
//         if (!$campagne->new_bachelier && $this->estNouveauBachelier($etudiant, $campagne)) {
//             $errors[] = 'Les nouveaux bacheliers ne sont pas acceptés pour cette campagne.';
//         }

//         // 6. Ancien bachelier
//         if (!$campagne->old_bachelier && !$this->estNouveauBachelier($etudiant, $campagne)) {
//             $errors[] = 'Seuls les nouveaux bacheliers sont acceptés pour cette campagne.';
//         }

//         // 7. Redoublement
//         if (!$campagne->redoublement && $this->estRedoublant($etudiant)) {
//             $errors[] = 'Les redoublants ne sont pas acceptés pour cette campagne.';
//         }

//         // 8. Réadmission
//         if (!$campagne->readmission && $this->estReadmission($etudiant)) {
//             $errors[] = 'Les réadmissions ne sont pas acceptées pour cette campagne.';
//         }

//         // 9. Étudiants internationaux
//         if (!$campagne->internationnaux && $this->estInternational($etudiant)) {
//             $errors[] = 'Les étudiants internationaux ne sont pas acceptés pour cette campagne.';
//         }

//         // 10. PMR — avertissement (pas un blocage)
//         if ($etudiant->autiste) {
//             $warnings[] = 'Étudiant PMR : vérification complémentaire recommandée.';
//         }

//         return $this->buildResult(empty($errors), $errors, $warnings, $campagne->id_campagne);
//     }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    // private function buildResult(
    //     bool   $eligible,
    //     array  $errors   = [],
    //     array  $warnings = [],
    //     ?string $campagneId = null
    // ): array {
    //     return compact('eligible', 'errors', 'warnings', 'campagneId');
    // }

    private function buildResult(bool $success, array $errors = [], array $warnings = [], $idCampagne = null, float $score = 0): array
{
    return [
        'success'     => $success,
        'errors'      => $errors,
        'warnings'    => $warnings,
        'id_campagne' => $idCampagne,
        'score'       => $score, // % de critères respectés
    ];
}

    private function estNouveauBachelier(Etudiant $etudiant, Campagne $campagne): bool
    {
        return (string)$etudiant->annee_academique_actuel === (string)$campagne->annee_debut;
    }

    private function estRedoublant(Etudiant $etudiant): bool
    {
        // Chargé via eager-load : pas de requête supplémentaire
        return $etudiant->relationLoaded('demandes')
            ? $etudiant->demandes->where('statut', 'acceptee')->count() > 0
            : $etudiant->demandes()->where('statut', 'acceptee')->exists();
    }

    private function estReadmission(Etudiant $etudiant): bool
    {
        $ancienResident = in_array($etudiant->statut_inscription, ['residents', 'annuler'], true);

        $aDejaDepose = $etudiant->relationLoaded('demandes')
            ? $etudiant->demandes->isNotEmpty()
            : $etudiant->demandes()->exists();

        return $ancienResident && $aDejaDepose;
    }

    private function estInternational(Etudiant $etudiant): bool
    {
        return !in_array(
            strtolower(trim($etudiant->nationalite ?? '')),
            ['sénégalaise', 'senegalaise'],
            true
        );
    }
}
