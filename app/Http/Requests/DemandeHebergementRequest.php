<?php

namespace App\Http\Requests;

use App\Http\Services\NouvelleDemandeService;
use App\Models\DemandeEtudiant;
use App\Models\Etudiant;

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

    public function rules()
    {
        $rules = parent::rules();
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
            'credit_valider.integer' => 'Le nombre de crédits validés doit être un nombre entier.',
            'credit_valider.min' => 'Le nombre de crédits validés doit être compris entre 0 et '.DemandeEtudiant::CREDITS_PAR_AN.'.',
            'credit_valider.max' => 'Le nombre de crédits validés doit être compris entre 0 et '.DemandeEtudiant::CREDITS_PAR_AN.'.',
        ]);
    }
}
