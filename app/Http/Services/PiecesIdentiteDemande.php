<?php

namespace App\Http\Services;

use App\Models\Campagne;
use App\Models\Document;
use App\Models\TypePiece;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Champ « Type de pièce » du formulaire web de demande d'hébergement (/register).
 *
 * Il propose les documents obligatoires de la campagne (paramétrés dans l'assistant de campagne) ;
 * si la campagne n'en exige aucun, il propose les types de pièce d'identité du référentiel `type_piece`.
 * Le choix reste enregistré dans `etudiants.type_piece_id` : un document choisi est rattaché au type de pièce
 * de même nom (ou de même libellé), créé au besoin, pour que l'affichage côté administration reste inchangé.
 * L'API mobile n'utilise pas ce service.
 */
class PiecesIdentiteDemande
{
    /** Documents obligatoires de la campagne, dans l'ordre d'affichage. */
    public function documentsRequis(?Campagne $campagne): Collection
    {
        if (! $campagne) {
            return collect();
        }

        return $campagne->loadMissing('documentCampagnes.documents')->documentCampagnes
            ->flatMap(fn ($lien) => $lien->documents)
            ->filter(fn (Document $document) => (bool) $document->champs_requis)
            ->unique('id_document')
            ->sortBy(fn (Document $document) => [(int) $document->ordre_affiche, $document->name])
            ->values();
    }

    /** Options du champ : `{id, libelle}`, documents obligatoires ou, à défaut, types de pièce. */
    public function options(?Campagne $campagne): Collection
    {
        $documents = $this->documentsRequis($campagne);
        if ($documents->isNotEmpty()) {
            return $documents->map(fn (Document $document) => ['id' => (string) $document->id_document, 'libelle' => (string) $document->name])->values();
        }

        return TypePiece::orderBy('name')->get()->map(fn (TypePiece $type) => ['id' => (string) $type->id, 'libelle' => (string) $type->name])->values();
    }

    /** Valeurs acceptées par la validation (identifiants des options proposées). */
    public function valeursAcceptees(?Campagne $campagne): array
    {
        return $this->options($campagne)->pluck('id')->all();
    }

    /** Identifiant `type_piece` à enregistrer pour la valeur choisie (document de la campagne ou type de pièce). */
    public function typePieceId(?Campagne $campagne, string $valeur): ?int
    {
        $document = $this->documentsRequis($campagne)->first(fn (Document $document) => (string) $document->id_document === $valeur);
        if (! $document) {
            return TypePiece::whereKey($valeur)->value('id');
        }

        $nom = mb_strtolower(trim((string) $document->name));
        $existant = TypePiece::query()
            ->whereRaw('LOWER(name) = ?', [$nom])
            ->orWhereRaw('LOWER(description) = ?', [$nom])
            ->value('id');

        return $existant ?? TypePiece::create([
            'name' => Str::limit(trim((string) $document->name), 50, ''),
            'description' => Str::limit(trim((string) $document->name), 50, ''),
        ])->id;
    }
}
