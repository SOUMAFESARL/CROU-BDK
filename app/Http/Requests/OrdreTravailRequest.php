<?php
// app/Http/Requests/Admin/OrdreTravailRequest.php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class OrdreTravailRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'titre' => 'required|string|max:255',
            'description' => 'required|string',
            'categorie' => 'required|in:electricite,eau,sanitaire,mobilier,securite,chauffage,climatisation,batiment,autre',
            'priorite' => 'required|in:critique,elevee,moyenne,faible',
            'type_intervention' => 'required|in:corrective,preventive,urgente',
            'cite_id' => 'nullable|exists:residences,id_residences',
            'batiment_id' => 'nullable|exists:batiments,id_batiments',
            'chambre_id' => 'nullable|exists:chambres,id_chambres',
            'etudiant_id' => 'nullable|exists:residents,id_residents',
            'equipement_id' => 'nullable|exists:equipements,id_equipements',
            'date_echeance' => 'nullable|date|after:today',
            'cause' => 'nullable|string',
            'cout_estime' => 'nullable|numeric|min:0',
            'pieces' => 'nullable|array',
            'pieces.*.id' => 'required|exists:pieces_detachees,id_pieces_detachees',
            'pieces.*.quantite' => 'required|integer|min:1',
            'pieces.*.prix_unitaire' => 'nullable|numeric|min:0',
        ];

        if ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
            // Règles pour la mise à jour
            $rules['titre'] = 'sometimes|required|string|max:255';
            $rules['description'] = 'sometimes|required|string';
            $rules['categorie'] = 'sometimes|required|in:electricite,eau,sanitaire,mobilier,securite,chauffage,climatisation,batiment,autre';
            $rules['priorite'] = 'sometimes|required|in:critique,elevee,moyenne,faible';
            $rules['type_intervention'] = 'sometimes|required|in:corrective,preventive,urgente';
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'titre.required' => 'Le titre est obligatoire',
            'description.required' => 'La description est obligatoire',
            'categorie.required' => 'La catégorie est obligatoire',
            'categorie.in' => 'La catégorie sélectionnée est invalide',
            'priorite.required' => 'La priorité est obligatoire',
            'priorite.in' => 'La priorité sélectionnée est invalide',
            'type_intervention.required' => 'Le type d\'intervention est obligatoire',
            'type_intervention.in' => 'Le type d\'intervention sélectionné est invalide',
            'date_echeance.after' => 'La date d\'échéance doit être dans le futur',
            'cout_estime.numeric' => 'Le coût estimé doit être un nombre',
            'cout_estime.min' => 'Le coût estimé doit être supérieur à 0',
        ];
    }
}