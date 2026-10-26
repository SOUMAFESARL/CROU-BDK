<?php

namespace App\Http\Services;

use App\Models\DemandeEtudiant;
use App\Models\Etudiant;
use App\Models\Filiere;
use App\Models\Genre;
use App\Models\groupeSanguin;
use App\Models\NiveauEtude;
use App\Models\Region;
use App\Models\TypePiece;
use App\Models\Ville;
use App\Support\Telephone;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * Modification par l'administration de toutes les informations d'un étudiant (fiche + informations académiques de sa dernière demande),
 * utilisée par la page des résidents et par la page de traitement d'une demande.
 * Le statut, l'état du dossier, l'affectation et la confirmation PMR ne sont pas modifiables ici (cycle géré par `DossierWorkflow`).
 */
class EtudiantEditionService
{
    /** Champs de la fiche étudiant (table `etudiants`). */
    private const CHAMPS_ETUDIANT = ['name', 'genre_id', 'date_naissance', 'lieu_naissance', 'nationalite', 'groupe_sanguin_id',
        'type_piece_id', 'numero_piece', 'date_expiration',
        'telephone1', 'telephone2', 'email1', 'email2', 'regions_id', 'ville_id', 'adresse', 'distance',
        'matricule', 'numero_etudiant', 'annee_academique_actuel',
        'boursier', 'type_bourse', 'allergies', 'maladies_chroniques', 'autiste',
        'name_tuteur', 'telephone_tuteur', 'adresse_tuteur'];

    /** Informations académiques portées par la dernière demande (table `demande`). */
    private const CHAMPS_DEMANDE = ['annee_academique_precedent', 'niveau_etude_precedent_code', 'niveau_etude_actuel_code',
        'filiere_code', 'moyenne', 'credit_valider'];

    private const BOOLEENS = ['boursier', 'allergies', 'maladies_chroniques', 'autiste'];

    private const DATES = ['date_naissance', 'date_expiration'];

    /** Valeurs initiales du formulaire (chaînes, sauf les cases à cocher). */
    public function valeurs(Etudiant $etudiant): array
    {
        $demande = $etudiant->derniereDemande;
        $texte = fn ($valeur) => $valeur === null ? '' : (string) $valeur;

        $valeurs = collect(self::CHAMPS_ETUDIANT)->mapWithKeys(fn ($champ) => [$champ => match (true) {
            in_array($champ, self::BOOLEENS, true) => (bool) $etudiant->{$champ},
            in_array($champ, self::DATES, true) => $etudiant->{$champ}?->toDateString() ?? '',
            default => $texte($etudiant->{$champ}),
        }])->all();

        foreach (self::CHAMPS_DEMANDE as $champ) {
            $valeurs[$champ] = $texte($demande?->{$champ});
        }
        if ($demande && $valeurs['moyenne'] !== '') {
            $valeurs['moyenne'] = rtrim(rtrim(number_format((float) $demande->moyenne, 2, '.', ''), '0'), '.');
        }

        return $valeurs + ['avec_demande' => (bool) $demande];
    }

    /** Référentiels des listes déroulantes du formulaire. */
    public function options(): array
    {
        $liste = fn ($items, string $cle = 'id') => $items
            ->map(fn ($item) => ['id' => (string) $item->{$cle}, 'name' => (string) $item->name])
            ->values();

        return [
            'genres' => $liste(Genre::query()->orderBy('name')->get()),
            'typesPiece' => $liste(TypePiece::query()->orderBy('name')->get()),
            'groupesSanguins' => $liste(groupeSanguin::query()->orderBy('name')->get()),
            'regions' => $liste(Region::query()->orderBy('name')->get()),
            'villes' => Ville::query()->orderBy('name')->get(['id', 'name', 'region_id'])
                ->map(fn ($ville) => ['id' => (string) $ville->id, 'name' => (string) $ville->name, 'region_id' => (string) $ville->region_id])
                ->values(),
            'niveaux' => $liste(NiveauEtude::query()->orderBy('name')->get(), 'id_niveau_etude'),
            'filieres' => $liste(Filiere::query()->orderBy('name')->get(), 'id_filiere'),
        ];
    }

    public function mettreAJour(Request $request, Etudiant $etudiant): void
    {
        $unique = fn (string $colonne) => Rule::unique('etudiants', $colonne)->ignore($etudiant->getKey(), 'id_etudiant');
        $demande = $etudiant->derniereDemande;

        Telephone::normaliserRequete($request, ['telephone1', 'telephone2', 'telephone_tuteur']);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'genre_id' => ['required', 'integer', Rule::exists('genre', 'id')],
            'date_naissance' => ['nullable', 'date', 'before:today'],
            'lieu_naissance' => ['nullable', 'string', 'max:255'],
            'nationalite' => ['nullable', 'string', 'max:100'],
            'groupe_sanguin_id' => ['nullable', 'integer', Rule::exists('groupe_sanguins', 'id')],
            'type_piece_id' => ['nullable', 'integer', Rule::exists('type_piece', 'id')],
            'numero_piece' => ['nullable', 'string', 'max:50', $unique('numero_piece')],
            'date_expiration' => ['nullable', 'date'],
            'telephone1' => ['required', Telephone::regle(), $unique('telephone1')],
            'telephone2' => ['nullable', Telephone::regle()],
            'email1' => ['nullable', 'email', 'max:255', $unique('email1')],
            'email2' => ['nullable', 'email', 'max:255'],
            'regions_id' => ['nullable', 'integer', Rule::exists('regions', 'id')],
            'ville_id' => ['nullable', 'integer', Rule::exists('villes', 'id')->where(fn ($q) => $request->filled('regions_id') ? $q->where('region_id', $request->input('regions_id')) : $q)],
            'adresse' => ['nullable', 'string', 'max:255'],
            'distance' => ['nullable', 'integer', 'min:0'],
            'matricule' => ['required', 'string', 'max:100', $unique('matricule')],
            'numero_etudiant' => ['nullable', 'string', 'max:100', $unique('numero_etudiant')],
            'annee_academique_actuel' => ['nullable', 'string', 'regex:/^\d{4}-\d{4}$/'],
            'boursier' => ['nullable', 'boolean'],
            'type_bourse' => ['nullable', 'string', 'max:100'],
            'allergies' => ['nullable', 'boolean'],
            'maladies_chroniques' => ['nullable', 'boolean'],
            'autiste' => ['nullable', 'boolean'],
            'name_tuteur' => ['nullable', 'string', 'max:255'],
            'telephone_tuteur' => ['nullable', Telephone::regle()],
            'adresse_tuteur' => ['nullable', 'string', 'max:255'],
            // Informations académiques : seulement si l'étudiant a une demande.
            'annee_academique_precedent' => ['nullable', 'string', 'regex:/^\d{4}-\d{4}$/'],
            'niveau_etude_precedent_code' => ['nullable', 'string', Rule::exists('niveau_etude', 'id_niveau_etude')],
            'niveau_etude_actuel_code' => ['nullable', 'string', Rule::exists('niveau_etude', 'id_niveau_etude')],
            'filiere_code' => ['nullable', Rule::exists('filiere', 'id_filiere')],
            'moyenne' => ['nullable', 'numeric', 'min:0', 'max:20'],
            'credit_valider' => ['nullable', 'integer', 'min:0', 'max:100'],
        ], [
            'matricule.unique' => 'Un autre étudiant possède déjà ce matricule.',
            'numero_etudiant.unique' => 'Un autre étudiant possède déjà ce numéro étudiant.',
            'numero_piece.unique' => 'Un autre étudiant possède déjà ce numéro de pièce.',
            'telephone1.unique' => 'Un autre étudiant possède déjà ce numéro de téléphone.',
            'email1.unique' => 'Un autre étudiant possède déjà cette adresse e-mail.',
            'date_naissance.before' => 'La date de naissance doit être antérieure à aujourd’hui.',
            'ville_id.exists' => 'La ville choisie n’appartient pas à la région sélectionnée.',
            'annee_academique_actuel.regex' => 'Format attendu : 2025-2026.',
            'annee_academique_precedent.regex' => 'Format attendu : 2025-2026.',
            'moyenne.max' => 'La moyenne doit être comprise entre 0 et 20.',
            'credit_valider.max' => 'Le pourcentage de crédits doit être compris entre 0 et 100.',
        ]);

        // Seuls les champs envoyés sont modifiés (formulaire complet ou ancien formulaire réduit).
        $fiche = collect($data)->only(self::CHAMPS_ETUDIANT)->all();
        foreach (self::BOOLEENS as $champ) {
            if (array_key_exists($champ, $fiche)) {
                $fiche[$champ] = (bool) $fiche[$champ];
            }
        }
        if (array_key_exists('boursier', $fiche) && ! $fiche['boursier']) {
            $fiche['type_bourse'] = null;
        }
        $academique = collect($data)->only(self::CHAMPS_DEMANDE)->all();
        if (array_key_exists('moyenne', $academique)) {
            $academique['moyenne'] ??= 0; // colonne demande.moyenne NOT NULL (défaut 0)
        }

        DB::transaction(function () use ($etudiant, $demande, $fiche, $academique) {
            $etudiant->update($fiche);

            if ($demande instanceof DemandeEtudiant) {
                if (array_key_exists('boursier', $fiche)) {
                    $academique['boursier'] = $fiche['boursier'];
                }
                if ($academique !== []) {
                    $demande->update($academique);
                }
            }
        });
    }
}
