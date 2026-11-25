<?php

namespace App\Http\Services;

use App\Http\Requests\DemandeHebergementRequest;
use App\Http\Services\Eligibilite\CriteresCampagne;
use App\Models\DemandeEtudiant;
use App\Models\DocumentEtudiant;
use App\Models\Etudiant;
use App\Models\Filiere;
use Barryvdh\DomPDF\Facade\Pdf;
use Barryvdh\DomPDF\PDF as DocumentPdf;
use Illuminate\Support\Facades\URL;

/**
 * Fiche de demande d'hébergement (PDF, vue `pdf.fiche-demande`) proposée à l'étudiant une fois sa demande soumise :
 * écran de confirmation de /register (lien signé, l'étudiant n'ayant pas encore de mot de passe) et « Mes demandes ».
 * Elle reprend les informations déclarées, jamais l'état du dossier : le résultat n'est connu qu'à la publication des listes.
 */
class FicheDemandeService
{
    /** Durée de validité du lien de l'écran de confirmation. */
    public const VALIDITE_HEURES = 2;

    /**
     * Lien signé de la fiche, proposé à la fin du formulaire public.
     * `$restreinte` : demande déposée sur un compte existant, reconnu sans mot de passe. La fiche ne reprend alors aucune
     * coordonnée ni pièce d'identité déjà enregistrée, que la personne devant l'écran n'a pas forcément saisie.
     */
    public function lienPublic(DemandeEtudiant $demande, bool $restreinte = false): string
    {
        // Signature du chemin seul : valable quels que soient le domaine ou le protocole vus derrière un proxy.
        return url(URL::temporarySignedRoute(
            'etudiant.demande.fiche',
            now()->addHours(self::VALIDITE_HEURES),
            ['demande' => $demande->getKey()] + ($restreinte ? ['restreinte' => 1] : []),
            false,
        ));
    }

    /** Lien de la fiche dans l'espace étudiant ; null tant que la demande est en brouillon. */
    public function lienEspaceEtudiant(DemandeEtudiant $demande): ?string
    {
        return $this->disponible($demande) ? route('etudiants.demandes.fiche', $demande->getKey()) : null;
    }

    /** La fiche n'existe que pour une demande soumise. */
    public function disponible(DemandeEtudiant $demande): bool
    {
        return $demande->etat_dossier !== 'brouillon';
    }

    public function pdf(DemandeEtudiant $demande, bool $restreinte = false): DocumentPdf
    {
        return Pdf::loadView('pdf.fiche-demande', $this->donnees($demande, $restreinte))
            ->setPaper('a4')
            ->setOptions(['isRemoteEnabled' => false, 'isPhpEnabled' => false]);
    }

    public function nomFichier(DemandeEtudiant $demande): string
    {
        return 'fiche-demande-'.preg_replace('/[^A-Za-z0-9-]+/', '-', $this->reference($demande)).'.pdf';
    }

    /** Contenu de la fiche : rubriques (titre → libellé → valeur) et pièces jointes de la campagne. */
    public function donnees(DemandeEtudiant $demande, bool $restreinte = false): array
    {
        $etudiant = Etudiant::findOrFail($demande->etudiant_id);
        $campagne = $demande->campagne;

        return [
            'reference' => $this->reference($demande),
            'deposeeLe' => ($demande->date_demande ?? $demande->created_at)?->format('d/m/Y à H:i'),
            'campagne' => collect([$campagne?->libelle, $campagne?->annee_campagne ?? $demande->annee_academique_actuel])
                ->filter()->unique()->implode(' · ') ?: '—',
            'restreinte' => $restreinte,
            'rubriques' => array_filter([
                'Identité' => $this->identite($etudiant, $restreinte),
                'Études' => $this->etudes($demande, $etudiant),
                'Situation' => $this->situation($demande),
                'Adresse' => $restreinte ? [] : $this->adresse($etudiant),
            ]),
            'pieces' => DocumentEtudiant::where('etudiant_id', $demande->etudiant_id)
                ->where('id_campagne', $demande->campagne_id)
                ->orderBy('created_at')->pluck('type_document')
                ->map(fn ($type) => $type ?: 'Document')->unique()->values()->all(),
        ];
    }

    private function reference(DemandeEtudiant $demande): string
    {
        return (string) ($demande->numero_demande ?: $demande->getKey());
    }

    private function identite(Etudiant $etudiant, bool $restreinte): array
    {
        if ($restreinte) {
            return $this->lignes(['Nom et prénoms' => $etudiant->name]);
        }

        return $this->lignes([
            'Civilité' => $etudiant->civilite?->name,
            'Nom et prénoms' => $etudiant->name,
            'Date et lieu de naissance' => collect([
                $etudiant->date_naissance?->format('d/m/Y'),
                filled($etudiant->lieu_naissance) ? 'à '.$etudiant->lieu_naissance : null,
            ])->filter()->implode(' '),
            'Nationalité' => $etudiant->nationalite,
            'Téléphone' => $etudiant->telephone1,
            'Adresse e-mail' => $etudiant->email1,
            'Pièce d’identité' => collect([
                $etudiant->typePiece?->name,
                filled($etudiant->numero_piece) ? 'n° '.$etudiant->numero_piece : null,
            ])->filter()->implode(' '),
            'Expiration de la pièce' => $etudiant->date_expiration?->format('d/m/Y'),
        ]);
    }

    /** Après la Terminale, la moyenne enregistrée (sur 20) est la note au bac : elle est restituée sur 400. */
    private function etudes(DemandeEtudiant $demande, Etudiant $etudiant): array
    {
        $precedent = $demande->niveauEtudiantPrecedent;
        $bachelier = $precedent !== null && CriteresCampagne::niveauSansAgeMax($precedent->name, $precedent->abreviation ?? null);
        $moyenne = filled($demande->moyenne) ? (float) $demande->moyenne : null;

        return $this->lignes([
            'Matricule universitaire' => $etudiant->matricule,
            'Numéro de table au bac' => $demande->num_table_bac,
            'Ville d’obtention du bac' => $demande->ville_bac,
            'Niveau précédent' => $precedent?->name,
            'Niveau actuel' => $demande->niveauEtudiantActuel?->name,
            'Filière' => filled($demande->filiere_code) ? Filiere::query()->whereKey($demande->filiere_code)->value('name') : null,
            'Moyenne générale' => ! $bachelier && $moyenne !== null ? $this->nombre($moyenne).' / 20' : null,
            'Note obtenue au bac' => $bachelier && $moyenne !== null
                ? $this->nombre($moyenne * DemandeHebergementRequest::NOTE_BAC_MAX / 20).' / '.DemandeHebergementRequest::NOTE_BAC_MAX : null,
            'Crédits validés' => filled($demande->credit_valider)
                ? $this->nombre((float) $demande->credit_valider).' / '.DemandeEtudiant::CREDITS_PAR_AN : null,
        ]);
    }

    private function situation(DemandeEtudiant $demande): array
    {
        return $this->lignes([
            'Boursier' => $demande->boursier ? 'Oui' : 'Non',
            'Observations' => $demande->commentaires,
        ]);
    }

    private function adresse(Etudiant $etudiant): array
    {
        return $this->lignes([
            'Région' => $etudiant->region?->name,
            'Ville' => $etudiant->ville?->name,
            'Adresse' => $etudiant->adresse,
            'Distance' => filled($etudiant->distance) ? $this->nombre((float) $etudiant->distance).' km' : null,
        ]);
    }

    /** Retire les lignes non renseignées. */
    private function lignes(array $lignes): array
    {
        return array_filter($lignes, fn ($valeur) => filled($valeur));
    }

    /** 12.50 → « 12,5 » ; 250.0 → « 250 ». */
    private function nombre(float $valeur): string
    {
        return rtrim(rtrim(number_format($valeur, 2, ',', ' '), '0'), ',');
    }
}
