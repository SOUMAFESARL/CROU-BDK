<?php

namespace App\Services;

use App\Models\DemandeEtudiant;
use App\Models\Etudiant;
use App\Models\Filiere;

/**
 * Civilité, filière et niveau d'étude d'un étudiant pour les documents (reçu de paiement, quittance de loyer) :
 * filière et niveau de sa demande de logement pour la campagne, à défaut de sa dernière demande (même source que
 * la fiche de demande).
 */
class ParcoursEtudiant
{
    /** Civilité d'un document : « Homme » → Monsieur, « Femme » → Madame (autre libellé du référentiel tel quel). */
    public function civilite(?Etudiant $etudiant): ?string
    {
        $genre = $etudiant?->civilite?->name;

        return match (mb_strtolower(trim((string) $genre))) {
            'homme', 'masculin', 'm' => 'Monsieur',
            'femme', 'féminin', 'feminin', 'f' => 'Madame',
            '' => null,
            default => $genre,
        };
    }

    /** @return array{filiere: ?string, niveau: ?string} */
    public function pour(?string $etudiantId, ?string $campagneId = null): array
    {
        $demandes = fn () => DemandeEtudiant::where('etudiant_id', $etudiantId)->latest('created_at');
        $demande = $etudiantId
            ? (($campagneId ? $demandes()->where('campagne_id', $campagneId)->first() : null) ?? $demandes()->first())
            : null;

        return [
            'filiere' => filled($demande?->filiere_code) ? Filiere::query()->whereKey($demande->filiere_code)->value('name') : null,
            'niveau' => $demande?->niveauEtudiantActuel?->name,
        ];
    }
}
