<?php

namespace App\Http\Services;

use App\Http\Requests\DemandeHebergementRequest;
use App\Http\Services\Eligibilite\CriteresCampagne;
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
 * Modification par l'administration des informations d'un étudiant (fiche + informations académiques de sa dernière demande),
 * utilisée par la page des résidents et par la page de traitement d'une demande.
 * Seules les informations saisies dans le formulaire de demande initiale sont modifiables ici (pas de téléphone/e-mail
 * secondaire, numéro étudiant, années académiques, type de bourse, santé ni tuteur) : les autres champs envoyés sont ignorés.
 * Le statut, l'état du dossier, l'affectation et la confirmation PMR ne sont pas modifiables ici (cycle géré par `DossierWorkflow`).
 *
 * Mêmes critères que le formulaire de demande (`DemandeHebergementRequest`) pour les informations d'études :
 * - matricule facultatif (un nouveau bachelier n'en a pas), unique s'il est saisi ;
 * - après la Terminale (nouveau bachelier) : ni matricule ni crédits (le matricule enregistré n'est pas modifié,
 *   les crédits sont effacés) et la moyenne est la note au bac saisie sur 400, enregistrée sur 20 ;
 * - sinon moyenne générale sur 20 (entre 1 et 20) et crédits validés entre 0 et 60 ;
 * - la Terminale n'est pas un niveau actuel possible et le niveau actuel ne peut pas être inférieur au précédent.
 * Les autres champs obligatoires du formulaire de demande restent facultatifs ici, pour ne pas bloquer la modification
 * d'une fiche ancienne ou incomplète.
 */
class EtudiantEditionService
{
    /** Champs de la fiche étudiant (table `etudiants`). */
    private const CHAMPS_ETUDIANT = ['name', 'genre_id', 'date_naissance', 'lieu_naissance', 'nationalite', 'groupe_sanguin_id',
        'type_piece_id', 'numero_piece', 'date_expiration',
        'telephone1', 'email1', 'regions_id', 'ville_id', 'adresse', 'distance',
        'matricule', 'boursier'];

    /** Informations académiques portées par la dernière demande (table `demande`). */
    private const CHAMPS_DEMANDE = ['niveau_etude_precedent_code', 'niveau_etude_actuel_code', 'filiere_code', 'moyenne', 'credit_valider'];

    private const BOOLEENS = ['boursier'];

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
        // Moyenne non renseignée : enregistrée 0 (colonne NOT NULL). Nouveau bachelier : note au bac restituée sur 400.
        if ($demande && $valeurs['moyenne'] !== '' && (float) $demande->moyenne > 0) {
            $moyenne = (float) $demande->moyenne;
            if ($this->bachelier($demande->niveau_etude_precedent_code)) {
                $moyenne = $moyenne * DemandeHebergementRequest::NOTE_BAC_MAX / 20;
            }
            $valeurs['moyenne'] = rtrim(rtrim(number_format($moyenne, 2, '.', ''), '0'), '.');
        } else {
            $valeurs['moyenne'] = '';
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
            // `terminale` : niveau précédent d'un nouveau bachelier (note au bac, ni matricule ni crédits), pas un niveau actuel.
            'niveaux' => NiveauEtude::query()->ordonnes()->get()
                ->map(fn ($niveau) => ['id' => (string) $niveau->id_niveau_etude, 'name' => (string) $niveau->name,
                    'terminale' => CriteresCampagne::niveauSansAgeMax($niveau->name, $niveau->abreviation ?? null)])
                ->values(),
            'filieres' => $liste(Filiere::query()->orderBy('name')->get(), 'id_filiere'),
        ];
    }

    public function mettreAJour(Request $request, Etudiant $etudiant): void
    {
        $unique = fn (string $colonne) => Rule::unique('etudiants', $colonne)->ignore($etudiant->getKey(), 'id_etudiant');
        $demande = $etudiant->derniereDemande;

        Telephone::normaliserRequete($request, ['telephone1']);
        // Niveau précédent saisi (ou celui de la demande) : Terminale = nouveau bachelier.
        $precedent = $request->has('niveau_etude_precedent_code') ? $request->input('niveau_etude_precedent_code') : $demande?->niveau_etude_precedent_code;
        $bachelier = $demande instanceof DemandeEtudiant && $this->bachelier($precedent);
        $noteMax = $bachelier ? DemandeHebergementRequest::NOTE_BAC_MAX : 20;
        $messageMoyenne = $bachelier ? 'La note au bac doit être comprise entre 1 et '.$noteMax.'.' : 'La moyenne doit être comprise entre 1 et 20.';
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
            'email1' => ['nullable', 'email', 'max:255', $unique('email1')],
            'regions_id' => ['nullable', 'integer', Rule::exists('regions', 'id')],
            'ville_id' => ['nullable', 'integer', Rule::exists('villes', 'id')->where(fn ($q) => $request->filled('regions_id') ? $q->where('region_id', $request->input('regions_id')) : $q)],
            'adresse' => ['nullable', 'string', 'max:255'],
            'distance' => ['nullable', 'integer', 'min:0'],
            // Facultatif comme dans le formulaire de demande ; pas demandé à un nouveau bachelier (valeur enregistrée conservée).
            'matricule' => $bachelier ? ['exclude'] : ['nullable', 'string', 'max:50', $unique('matricule')],
            'boursier' => ['nullable', 'boolean'],
            // Informations académiques : seulement si l'étudiant a une demande.
            'niveau_etude_precedent_code' => ['nullable', 'string', Rule::exists('niveau_etude', 'id_niveau_etude')],
            'niveau_etude_actuel_code' => ['nullable', 'string', Rule::exists('niveau_etude', 'id_niveau_etude'), function ($attribut, $valeur, $echec) use ($precedent) {
                if ($this->bachelier($valeur)) {
                    $echec('La Terminale ne peut pas être le niveau d’étude actuel.');

                    return;
                }
                $rangs = NiveauEtude::ordonnes()->pluck('id_niveau_etude')->map(fn ($id) => (string) $id)->flip();
                $actuel = $rangs[(string) $valeur] ?? null;
                $avant = $rangs[(string) $precedent] ?? null;
                if ($actuel !== null && $avant !== null && $actuel < $avant) {
                    $echec('Le niveau d’étude actuel ne peut pas être inférieur au niveau d’étude précédent.');
                }
            }],
            'filiere_code' => ['nullable', Rule::exists('filiere', 'id_filiere')],
            'moyenne' => ['nullable', 'numeric', 'min:1', 'max:'.$noteMax],
            'credit_valider' => $bachelier ? ['exclude'] : ['nullable', 'integer', 'min:0', 'max:'.DemandeEtudiant::CREDITS_PAR_AN],
        ], [
            'matricule.unique' => 'Un autre étudiant possède déjà ce matricule.',
            'numero_piece.unique' => 'Un autre étudiant possède déjà ce numéro de pièce.',
            'telephone1.unique' => 'Un autre étudiant possède déjà ce numéro de téléphone.',
            'email1.unique' => 'Un autre étudiant possède déjà cette adresse e-mail.',
            'date_naissance.before' => 'La date de naissance doit être antérieure à aujourd’hui.',
            'ville_id.exists' => 'La ville choisie n’appartient pas à la région sélectionnée.',
            'matricule.max' => 'Le matricule ne doit pas dépasser 50 caractères.',
            'moyenne.numeric' => 'La note doit être un nombre.',
            'moyenne.min' => $messageMoyenne,
            'moyenne.max' => $messageMoyenne,
            'credit_valider.max' => 'Le nombre de crédits validés doit être compris entre 0 et '.DemandeEtudiant::CREDITS_PAR_AN.'.',
        ]);

        // Seuls les champs envoyés sont modifiés (formulaire complet ou ancien formulaire réduit).
        $fiche = collect($data)->only(self::CHAMPS_ETUDIANT)->all();
        foreach (self::BOOLEENS as $champ) {
            if (array_key_exists($champ, $fiche)) {
                $fiche[$champ] = (bool) $fiche[$champ];
            }
        }
        // Plus boursier : le type de bourse éventuellement connu n'a plus lieu d'être.
        if (array_key_exists('boursier', $fiche) && ! $fiche['boursier']) {
            $fiche['type_bourse'] = null;
        }
        $academique = collect($data)->only(self::CHAMPS_DEMANDE)->all();
        if (array_key_exists('moyenne', $academique)) {
            // Note au bac (/400) enregistrée sur 20, comme dans le formulaire de demande.
            if ($bachelier && $academique['moyenne'] !== null) {
                $academique['moyenne'] = round((float) $academique['moyenne'] * 20 / $noteMax, 2);
            }
            $academique['moyenne'] ??= 0; // colonne demande.moyenne NOT NULL (défaut 0)
        }
        // Nouveau bachelier : pas de crédits validés (comme dans le formulaire de demande).
        if ($bachelier) {
            $academique['credit_valider'] = null;
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

    /** Vrai si le niveau précédent est la Terminale (nouveau bachelier). */
    private function bachelier(mixed $niveauPrecedent): bool
    {
        return filled($niveauPrecedent) && DemandeHebergementRequest::estTerminale((string) $niveauPrecedent);
    }
}
