<?php

namespace App\Http\Services\Eligibilite;

use App\Http\Requests\DemandeHebergementRequest;
use App\Http\Services\CatalogueSanctions;
use App\Http\Services\NoteConduite;
use App\Models\Campagne;
use App\Models\DemandeEtudiant;
use App\Models\Etudiant;
use App\Models\Filiere;
use App\Models\Loyer;
use App\Models\NiveauEtude;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;

/**
 * Conformité d'une demande aux critères de rejet de son volet (docs/notes/eligibilite-criteres-campagne.md),
 * affichée à l'agent sur la page de traitement d'une demande.
 *
 * - Volet : admission (nouvel étudiant) ou réadmission (ancien résident), selon `CriteresCampagne::voletEtudiant()`.
 * - Règles : version en vigueur du volet et du paramétrage général de la campagne de la demande, sinon modèle CROU.
 * - Seuls les critères qui s'appliquent à la demande sont listés : un critère désactivé, un mérite d'un autre public
 *   (note du bac / crédits) ou un niveau sans âge maximum n'apparaissent pas.
 *
 * Chaque critère est comparé aux valeurs saisies par l'étudiant : `ok` (conforme), `nok` (non conforme),
 * `nd` (information non renseignée) ou `nv` (non vérifiable par cet agent). Le résultat est indicatif : il ne modifie
 * pas le dossier (la liste provisoire et `DossierWorkflow` décident).
 */
class ConformiteDemande
{
    public const VOLETS = ['admission' => 'Admission', 'readmission' => 'Réadmission'];

    /** Règles lues par campagne et par volet : une seule lecture quand une liste de demandes est évaluée. */
    private array $reglesLues = [];

    public function __construct(
        private CriteresCampagne $criteres,
        private CatalogueSanctions $sanctions,
        private NoteConduite $conduite,
    ) {}

    /**
     * @param  bool  $discipline  l'agent peut consulter la situation disciplinaire (sanctions, note de conduite)
     */
    public function evaluer(Etudiant $etudiant, DemandeEtudiant $demande, bool $discipline): array
    {
        $campagne = $demande->campagne;
        $volet = $this->criteres->voletEtudiant($campagne, (string) $etudiant->getKey());
        [$vigueur, $regles, $general] = $this->reglesLues[($campagne?->getKey() ?? '').'|'.$volet] ??= $this->lireRegles($campagne, $volet);

        $liste = $volet === 'admission'
            ? $this->admission($regles, $general, $etudiant, $demande)
            : $this->readmission($regles, $general, $etudiant, $demande, $campagne, $discipline);
        $conformes = collect($liste)->where('etat', 'ok')->count();

        return [
            'volet' => $volet,
            'volet_libelle' => self::VOLETS[$volet],
            'campagne' => $campagne?->annee_campagne ?? $demande->annee_academique_actuel,
            // Règles lues : version en vigueur du volet, sinon modèle CROU (critères pas encore appliqués).
            'origine' => $vigueur ? 'en_vigueur' : 'modele',
            'version' => $vigueur?->version,
            'regles' => $liste,
            'conformes' => $conformes,
            'non_conformes' => collect($liste)->where('etat', 'nok')->count(),
            'score' => $liste ? (int) round($conformes / count($liste) * 100) : 100,
        ];
    }

    /** @return array{0: ?\App\Models\CampagneRegles, 1: array, 2: array} version en vigueur du volet, règles du volet, paramétrage général */
    private function lireRegles(?Campagne $campagne, string $volet): array
    {
        $vigueur = $campagne && Schema::hasTable('campagne_regles') ? $this->criteres->enVigueur($campagne, $volet) : null;
        $generalVigueur = $campagne && Schema::hasTable('campagne_regles') ? $this->criteres->enVigueur($campagne, 'general') : null;

        return [
            $vigueur,
            $this->criteres->completer($volet, $vigueur?->parametres ?? [], $campagne),
            $this->criteres->completer('general', $generalVigueur?->parametres ?? [], $campagne),
        ];
    }

    /**
     * Admission : mérite du public de l'étudiant (note du bac ou crédits), âge maximum, internationaux.
     * Pas de critère disciplinaire : les sanctions ne concernent que les anciens résidents (réadmission).
     */
    private function admission(array $regles, array $general, Etudiant $etudiant, DemandeEtudiant $demande): array
    {
        $merites = $regles['admission']['merite'] ?? [];
        $precedent = $demande->niveauEtudiantPrecedent;
        $moyenne = $this->nombre($demande->moyenne);
        $liste = [];

        if ($precedent === null) {
            if (collect($merites)->contains(fn ($merite) => ! empty($merite['actif']))) {
                $liste[] = $this->regle('Mérite', 'Note du bac ou crédits validés selon le niveau précédent', 'Niveau précédent non renseigné', 'nd');
            }
        } elseif (CriteresCampagne::niveauSansAgeMax($precedent->name, $precedent->abreviation ?? null)) {
            // Nouveau bachelier : moyenne enregistrée sur 20, seuil de la campagne sur 400.
            $merite = $merites['moyenne_bac_desc'] ?? [];
            if (! empty($merite['actif'])) {
                $max = DemandeHebergementRequest::NOTE_BAC_MAX;
                $note = $moyenne === null ? null : round($moyenne * $max / 20, 2);
                $liste[] = $this->seuil('Note du baccalauréat', $note, $this->nombre($merite['seuil'] ?? null), '/'.$max);
            }
        } else {
            $merite = $merites['credits_desc'] ?? [];
            if (! empty($merite['actif'])) {
                $credits = $this->nombre($demande->credit_valider);
                $liste[] = $this->seuil('Crédits validés', $credits, $this->nombre($merite['seuil'] ?? null), '/'.DemandeEtudiant::CREDITS_PAR_AN);
            }
        }

        // L'âge maximum par niveau du paramétrage général s'applique toujours à l'admission.
        $liste[] = $this->age($general, $etudiant, $demande->niveauEtudiantActuel);
        $liste[] = $this->internationaux($regles, $etudiant);

        return array_values(array_filter($liste));
    }

    /** Réadmission : demande, loyers, crédits par filière, conduite, âge maximum, internationaux, sanctions. */
    private function readmission(array $regles, array $general, Etudiant $etudiant, DemandeEtudiant $demande, ?Campagne $campagne, bool $discipline): array
    {
        $readmission = $regles['readmission'] ?? [];
        $depot = $demande->created_at ? Carbon::parse($demande->created_at)->format('d/m/Y') : null;
        $liste = [$this->regle('Demande de réadmission', 'Obligatoire', $depot ? 'Déposée le '.$depot : 'Déposée', 'ok')];

        if (! empty($readmission['loyers']['actif'])) {
            $liste[] = $this->loyers((int) ($readmission['loyers']['mois_exiges'] ?? 0), $etudiant, $demande, $campagne);
        }
        if (! empty($readmission['credits']['actif'])) {
            $liste[] = $this->creditsFiliere($readmission['credits'], $demande);
        }
        if (! empty($readmission['conduite']['actif']) && ($readmission['conduite']['note_min'] ?? null) !== null) {
            $liste[] = $this->noteConduite((float) $readmission['conduite']['note_min'], $etudiant, $discipline);
        }
        if (! empty($readmission['age_actif'])) {
            $liste[] = $this->age($general, $etudiant, $demande->niveauEtudiantActuel);
        }
        $liste[] = $this->internationaux($regles, $etudiant);
        $liste[] = $this->sanctionsBloquantes($etudiant, $discipline);

        return array_values(array_filter($liste));
    }

    /** Âge maximum du niveau actuel (paramétrage général) ; null si ce niveau n'a pas de limite. */
    private function age(array $general, Etudiant $etudiant, ?NiveauEtude $niveau): ?array
    {
        $ages = collect($general['age_max'] ?? []);
        if ($ages->isEmpty()) {
            return null;
        }
        if ($niveau === null) {
            return $this->regle('Âge maximum', 'Selon le niveau d’études', 'Niveau actuel non renseigné', 'nd');
        }
        $ligne = $ages->first(fn ($ligne) => (string) ($ligne['niveau'] ?? '') === (string) $niveau->getKey());
        if ($ligne === null) {
            return null;
        }
        $maximum = (int) $ligne['age'];
        $age = $etudiant->date_naissance ? Carbon::parse($etudiant->date_naissance)->age : null;
        $label = 'Âge maximum ('.($niveau->name ?: $niveau->abreviation).')';

        return $age === null
            ? $this->regle($label, '≤ '.$maximum.' ans', 'Date de naissance non renseignée', 'nd')
            : $this->regle($label, '≤ '.$maximum.' ans', $age.' ans', $age <= $maximum ? 'ok' : 'nok');
    }

    private function internationaux(array $regles, Etudiant $etudiant): array
    {
        $acceptes = (bool) ($regles['recevabilite']['internationaux'] ?? false);
        $nationalite = trim((string) $etudiant->nationalite);
        if ($nationalite === '') {
            return $this->regle('Étudiants internationaux', $acceptes ? 'Acceptés' : 'Non acceptés', 'Nationalité non renseignée', $acceptes ? 'ok' : 'nd');
        }
        $international = ! in_array(mb_strtolower($nationalite), ['ivoirienne', 'ivoirien'], true);

        return $this->regle(
            'Étudiants internationaux',
            $acceptes ? 'Acceptés' : 'Non acceptés',
            $international ? 'International ('.$nationalite.')' : 'Ivoirien',
            ! $international || $acceptes ? 'ok' : 'nok',
        );
    }

    /** Sanction bloquante en cours (suspension, expulsion) : la demande ne peut pas aboutir. */
    private function sanctionsBloquantes(Etudiant $etudiant, bool $discipline): array
    {
        $label = 'Sanction bloquante';
        $regle = 'Aucune suspension ni expulsion en cours';
        if (! $discipline) {
            return $this->regle($label, $regle, 'Information réservée au suivi disciplinaire', 'nv');
        }
        $statut = $etudiant->statut_disciplinaire;
        $bloquant = $this->sanctions->estBloquant($statut);

        return $this->regle($label, $regle, $statut === CatalogueSanctions::AUCUNE ? 'Aucune sanction' : $this->sanctions->libelle($statut), $bloquant ? 'nok' : 'ok');
    }

    /** Mois de loyer payés (soldés) sur l'année universitaire précédant celle de la campagne. */
    private function loyers(int $exiges, Etudiant $etudiant, DemandeEtudiant $demande, ?Campagne $campagne): array
    {
        $annee = $this->anneePrecedente($campagne?->annee_campagne ?? $demande->annee_academique_actuel) ?? $demande->annee_academique_precedent;
        $regle = '≥ '.$exiges.' mois payés'.($annee ? ' ('.$annee.')' : '');
        if (! $annee || ! Schema::hasTable('loyers')) {
            return $this->regle('Loyers à jour', $regle, 'Année universitaire précédente inconnue', 'nd');
        }
        $payes = Loyer::query()
            ->where('etudiant_id', $etudiant->getKey())
            ->where('annee_universitaire', $annee)
            ->where('statut', '!=', 'annule')
            ->where('solde_restant', '<=', 0)
            ->get(['mois_facturation', 'annee_facturation'])
            ->unique(fn ($loyer) => $loyer->annee_facturation.'-'.$loyer->mois_facturation)
            ->count();

        return $this->regle('Loyers à jour', $regle, $payes.' mois payé'.($payes > 1 ? 's' : ''), $payes >= $exiges ? 'ok' : 'nok');
    }

    /** Pourcentage des crédits de l'année exigé : seuil de la filière de l'étudiant s'il en a un, sinon seuil commun. */
    private function creditsFiliere(array $credits, DemandeEtudiant $demande): array
    {
        $ligne = filled($demande->filiere_code)
            ? collect($credits['filieres'] ?? [])->first(fn ($ligne) => (string) ($ligne['filiere_id'] ?? '') === (string) $demande->filiere_code)
            : null;
        $seuil = (float) ($ligne['seuil'] ?? $credits['seuil_defaut'] ?? 100);
        $regle = '≥ '.$this->texte($seuil).' % des crédits';
        if ($ligne) {
            $filiere = Filiere::query()->whereKey($ligne['filiere_id'])->value('name');
            $regle .= ' (filière '.($filiere ?: 'de l’étudiant').(! empty($ligne['concours']) ? ', école sur concours' : '').')';
        }
        $valides = $this->nombre($demande->credit_valider);
        if ($valides === null) {
            return $this->regle('Crédits validés', $regle, 'Non renseignés', 'nd');
        }
        $pourcentage = DemandeEtudiant::pourcentageCredits($valides);

        return $this->regle(
            'Crédits validés',
            $regle,
            $this->texte($valides).'/'.DemandeEtudiant::CREDITS_PAR_AN.' ('.$this->texte($pourcentage).' %)',
            $pourcentage >= $seuil ? 'ok' : 'nok',
        );
    }

    private function noteConduite(float $minimum, Etudiant $etudiant, bool $discipline): array
    {
        $initiale = $this->sanctions->noteInitiale();
        $regle = '≥ '.$this->texte($minimum).'/'.$this->texte($initiale);
        if (! $discipline) {
            return $this->regle('Note de conduite', $regle, 'Information réservée au suivi disciplinaire', 'nv');
        }
        $note = $this->conduite->note((string) $etudiant->getKey());

        return $this->regle('Note de conduite', $regle, $this->texte($note).'/'.$this->texte($initiale), $note >= $minimum ? 'ok' : 'nok');
    }

    /** Valeur comparée à un seuil minimum (un seuil vide = aucun minimum). */
    private function seuil(string $label, ?float $valeur, ?float $seuil, string $unite): array
    {
        $regle = $seuil === null ? 'Aucun minimum' : '≥ '.$this->texte($seuil).$unite;
        if ($valeur === null) {
            return $this->regle($label, $regle, 'Non renseigné'.(str_starts_with($label, 'Note') ? 'e' : 's'), 'nd');
        }

        return $this->regle($label, $regle, $this->texte($valeur).$unite, $seuil === null || $valeur >= $seuil ? 'ok' : 'nok');
    }

    /** « 2026-2027 » → « 2025-2026 » ; null pour un autre format. */
    private function anneePrecedente(?string $annee): ?string
    {
        return preg_match('/^(\d{4})-(\d{4})$/', (string) $annee, $m) ? ($m[1] - 1).'-'.($m[2] - 1) : null;
    }

    private function nombre($valeur): ?float
    {
        return $valeur === null || $valeur === '' || ! is_numeric($valeur) ? null : (float) $valeur;
    }

    /** Nombre à la française, sans zéros inutiles (12,5 ; 245). */
    private function texte(float $valeur): string
    {
        return str_replace('.', ',', rtrim(rtrim(number_format($valeur, 2, '.', ''), '0'), '.'));
    }

    private function regle(string $label, string $campagne, string $etudiant, string $etat): array
    {
        return ['label' => $label, 'campagne' => $campagne, 'etudiant' => $etudiant, 'etat' => $etat];
    }
}
