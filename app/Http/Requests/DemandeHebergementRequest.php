<?php

namespace App\Http\Requests;

use App\Http\Services\Eligibilite\CriteresCampagne;
use App\Http\Services\NouvelleDemandeService;
use App\Models\DemandeEtudiant;
use App\Models\Etudiant;
use App\Models\NiveauEtude;

/**
 * Formulaire public de demande d'hébergement (/register, frontedController::postRegister) : mêmes règles qu'EtudiantRequest,
 * mais un étudiant qui a déjà un compte peut déposer une demande à chaque nouvelle campagne s'il saisit
 * l'e-mail et le matricule de son compte. L'API mobile garde EtudiantRequest.
 */
class DemandeHebergementRequest extends EtudiantRequest
{
    private Etudiant|false|null $compteExistant = false;

    /** Compte existant reconnu par son e-mail et son matricule, ou null pour une première inscription. */
    public function compteExistant(): ?Etudiant
    {
        if ($this->compteExistant === false) {
            $this->compteExistant = app(NouvelleDemandeService::class)->compteExistant(
                $this->input('email'), $this->input('matricule'), $this->input('telephone'),
            );
        }

        return $this->compteExistant;
    }

    /** Note au baccalauréat saisie sur 400 ; stockée sur 20 comme les moyennes (critères, classement). */
    public const NOTE_BAC_MAX = 400;

    private ?bool $apresTerminale = null;

    /** Vrai si le niveau précédent saisi est la Terminale (nouveau bachelier). */
    public function apresTerminale(): bool
    {
        return $this->apresTerminale ??= self::estTerminale((string) $this->input('niveau_precedent', ''));
    }

    /**
     * Redoublant si le niveau précédent est le niveau actuel (plus de case à cocher) ;
     * après la Terminale, ni crédits ni matricule : la moyenne saisie est la note au bac (/400), la ville et le numéro de table du bac
     * sont demandés ; sinon, ni la ville ni le numéro de table du bac ne sont demandés.
     * L'année académique précédente n'est plus saisie : elle se déduit de celle de la campagne (2026-2027 → 2025-2026).
     */
    protected function prepareForValidation(): void
    {
        parent::prepareForValidation();
        $precedent = (string) $this->input('niveau_precedent', '');
        $donnees = ['redoublement' => $precedent !== '' && $precedent === (string) $this->input('niveau', '') ? 1 : 0];
        if (preg_match('/^(\d{4})-(\d{4})$/', (string) $this->input('annee_academique_actuel', ''), $annee)) {
            $donnees['annee_academique_precedent'] = ($annee[1] - 1).'-'.($annee[2] - 1);
        }
        if ($this->apresTerminale()) {
            $donnees['credit_valider'] = null;
            $donnees['matricule'] = null;
            if (is_string($this->input('num_table_bac'))) {
                $donnees['num_table_bac'] = mb_strtoupper(trim($this->input('num_table_bac')));
            }
        } else {
            $donnees['ville_bac'] = null;
            $donnees['num_table_bac'] = null;
        }
        $this->merge($donnees);
    }

    /** Note au bac ramenée sur 20 avant l'enregistrement, puis traitement des saisies libres. */
    protected function passedValidation(): void
    {
        if ($this->apresTerminale() && is_numeric($this->input('moyenne'))) {
            $this->merge(['moyenne' => round((float) $this->input('moyenne') * 20 / self::NOTE_BAC_MAX, 2)]);
        }
        parent::passedValidation();
    }

    /** Vrai si l'identifiant désigne le niveau Terminale (libellé ou abréviation). */
    public static function estTerminale(?string $niveauId): bool
    {
        $niveau = $niveauId ? NiveauEtude::find($niveauId) : null;

        return $niveau !== null && CriteresCampagne::niveauSansAgeMax($niveau->name, $niveau->abreviation ?? null);
    }

    public function rules()
    {
        $rules = parent::rules();
        // Matricule universitaire obligatoire, sauf après la Terminale (un nouveau bachelier n'en a pas encore : numéro de table au bac).
        // Le champ n'est affiché qu'une fois le niveau précédent choisi.
        $rules['matricule'] = [$this->filled('niveau_precedent') && ! $this->apresTerminale() ? 'required' : 'nullable', 'string', 'max:50', 'unique:etudiants,matricule'];
        // La Terminale n'est pas un niveau d'études « actuel » possible, et le niveau actuel ne peut pas être inférieur au précédent.
        $rules['niveau'] = ['required', function ($attribut, $valeur, $echec) {
            if (self::estTerminale((string) $valeur)) {
                $echec('La Terminale ne peut pas être votre niveau d’étude actuel.');

                return;
            }
            $rangs = NiveauEtude::ordonnes()->pluck('id_niveau_etude')->map(fn ($id) => (string) $id)->flip();
            $actuel = $rangs[(string) $valeur] ?? null;
            $precedent = $rangs[(string) $this->input('niveau_precedent', '')] ?? null;
            if ($actuel !== null && $precedent !== null && $actuel < $precedent) {
                $echec('Le niveau d’étude actuel ne peut pas être inférieur au niveau d’étude précédent.');
            }
        }];
        $rules['niveau_precedent'] = ['required'];
        // Après la Terminale : note au bac sur 400 et ville du bac obligatoire ; sinon moyenne générale sur 20.
        $rules['moyenne'] = $this->apresTerminale()
            ? ['nullable', 'numeric', 'min:1', 'max:'.self::NOTE_BAC_MAX]
            : ['nullable', 'numeric', 'min:1', 'max:20'];
        $rules['ville_bac'] = $this->apresTerminale() ? ['required', 'string', 'max:150'] : ['nullable'];
        $rules['num_table_bac'] = $this->apresTerminale() ? ['required', 'string', 'max:50'] : ['nullable'];
        // Nombre de crédits validés sur l'année précédente (60 crédits par an), pas un pourcentage.
        $rules['credit_valider'] = ['nullable', 'integer', 'min:0', 'max:'.DemandeEtudiant::CREDITS_PAR_AN];
        if ($this->compteExistant()) {
            // Identifiants du compte existant : pas de contrôle d'unicité, le compte est réutilisé tel quel.
            foreach (['telephone', 'email', 'num_piece', 'matricule'] as $champ) {
                $rules[$champ] = collect(is_array($rules[$champ]) ? $rules[$champ] : explode('|', $rules[$champ]))
                    ->reject(fn ($regle) => is_string($regle) && str_starts_with($regle, 'unique:'))
                    ->values()->all();
            }
        }

        return $rules;
    }

    /** Compte reconnu : pas de contrôle d'identité existante (téléphone, pièce) d'EtudiantRequest. */
    public function withValidator($validator): void
    {
        if (! $this->compteExistant()) {
            parent::withValidator($validator);
        }
    }

    public function messages()
    {
        $existant = 'Un compte existe déjà avec cette information. Pour une nouvelle demande, saisissez l’e-mail et le matricule de votre compte (si votre compte n’a pas d’e-mail : votre matricule et le téléphone enregistré).';

        return array_merge(parent::messages(), [
            'email.unique' => $existant,
            'telephone.unique' => $existant,
            'num_piece.unique' => $existant,
            'matricule.unique' => $existant,
            'niveau.required' => 'Choisissez votre niveau d’étude actuel.',
            'niveau_precedent.required' => 'Choisissez votre niveau d’étude précédent.',
            'ville_bac.required' => 'La ville/commune d’obtention du bac est obligatoire.',
            'ville_bac.max' => 'La ville/commune d’obtention du bac ne doit pas dépasser 150 caractères.',
            'num_table_bac.required' => 'Le numéro de table au bac est obligatoire.',
            'num_table_bac.max' => 'Le numéro de table au bac ne doit pas dépasser 50 caractères.',
            'matricule.required' => 'Le matricule universitaire est obligatoire.',
            'matricule.max' => 'Le matricule ne doit pas dépasser 50 caractères.',
            'moyenne.numeric' => 'La note doit être un nombre.',
            'moyenne.min' => $this->apresTerminale() ? 'La note au bac doit être comprise entre 1 et '.self::NOTE_BAC_MAX.'.' : 'La moyenne doit être comprise entre 1 et 20.',
            'moyenne.max' => $this->apresTerminale() ? 'La note au bac doit être comprise entre 1 et '.self::NOTE_BAC_MAX.'.' : 'La moyenne doit être comprise entre 1 et 20.',
            'credit_valider.integer' => 'Le nombre de crédits validés doit être un nombre entier.',
            'credit_valider.min' => 'Le nombre de crédits validés doit être compris entre 0 et '.DemandeEtudiant::CREDITS_PAR_AN.'.',
            'credit_valider.max' => 'Le nombre de crédits validés doit être compris entre 0 et '.DemandeEtudiant::CREDITS_PAR_AN.'.',
        ]);
    }
}
