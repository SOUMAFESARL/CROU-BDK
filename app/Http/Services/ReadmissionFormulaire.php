<?php

namespace App\Http\Services;

use App\Http\Services\Eligibilite\CriteresCampagne;
use App\Models\DemandeEtudiant;
use App\Models\Document;
use App\Models\DocumentCampagne;
use App\Models\Etudiant;
use App\Models\Filiere;
use App\Models\NiveauEtude;
use Illuminate\Support\Collection;

/**
 * Données du formulaire web de réadmission (page et contrôle de l'envoi) : niveaux proposés à l'étudiant
 * et pièces demandées par la campagne, telles que paramétrées par l'administration.
 * L'API mobile (ReadmissionController) n'utilise pas cette classe : son contrat reste inchangé.
 */
class ReadmissionFormulaire
{
    /** Formats que le stockage des pièces accepte (DocumentEtudiantRepository::stockerPieces). */
    public const FORMATS = ['pdf', 'jpg', 'jpeg', 'png'];

    /**
     * Niveaux dans l'ordre du paramétrage (Licence 1 → doctorat), sans la Terminale, et à partir du dernier niveau
     * actuel déclaré par l'étudiant dans une demande précédente (redoublement possible).
     *
     * @return Collection<int, NiveauEtude>
     */
    public function niveauxProposes(Etudiant $etudiant): Collection
    {
        $niveaux = NiveauEtude::ordonnes()->get()
            ->reject(fn ($niveau) => CriteresCampagne::niveauSansAgeMax($niveau->name, $niveau->abreviation ?? null))
            ->values();

        $dernierNiveau = DemandeEtudiant::where('etudiant_id', $etudiant->getKey())
            ->whereNotNull('niveau_etude_actuel_code')
            ->latest('created_at')
            ->value('niveau_etude_actuel_code');
        $rang = $niveaux->search(fn ($niveau) => (string) $niveau->getKey() === (string) $dernierNiveau);

        return $rang === false ? $niveaux : $niveaux->slice($rang)->values();
    }

    /** Filière de la dernière demande de l'étudiant qui en indique une, si elle existe encore dans le référentiel. */
    public function filierePrecedente(Etudiant $etudiant): ?string
    {
        $filiere = DemandeEtudiant::where('etudiant_id', $etudiant->getKey())
            ->whereNotNull('filiere_code')
            ->where('filiere_code', '!=', '')
            ->latest('created_at')
            ->value('filiere_code');

        return $filiere !== null && Filiere::whereKey($filiere)->exists() ? (string) $filiere : null;
    }

    /**
     * Pièces demandées par la campagne, dans l'ordre d'affichage du paramétrage.
     *
     * @return Collection<int, Document>
     */
    public function documentsCampagne(?string $campagneId): Collection
    {
        if (! $campagneId) {
            return collect();
        }

        return DocumentCampagne::with('documents')
            ->where('id_campagne', $campagneId)
            ->get()
            ->flatMap(fn ($lien) => $lien->documents)
            ->unique('id_document')
            ->sortBy('ordre_affiche')
            ->values();
    }

    /** Formats déclarés sur le document, limités à ceux que le stockage accepte (tous si rien n'est déclaré). */
    public function formats(Document $document): array
    {
        $formats = collect(preg_split('/[\s,;|]+/', (string) $document->format_accepte))
            ->map(fn ($format) => strtolower(ltrim($format, '.')))
            ->intersect(self::FORMATS)
            ->values()
            ->all();

        return $formats ?: self::FORMATS;
    }
}
