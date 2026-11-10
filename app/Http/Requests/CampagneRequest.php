<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CampagneRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {


        $campagneId = $this->route('campagne');

        return [
//            'annee_campagne' => [
//                'required',
//                'string',
//                'regex:/^\d{4}-\d{4}$/',
//                function ($attribute, $value, $fail) {
//                    list($debut, $fin) = explode('-', $value);
//                    if ((int)$fin !== (int)$debut + 1) {
//                        $fail('L\'année académique doit être consécutive (ex: 2025-2026)');
//                    }
//                },
//                Rule::unique('campagne', 'annee_campagne')->ignore($campagneId, 'id_campagne')
//            ],
            'annee_campagne' => 'required|string|regex:/^\d{4}-\d{4}$/',
            'libelle' => 'required|string|max:150',
            'description' => 'nullable|string|max:1000',
            'periode_debut' => 'required|date|before:periode_fin',
            'periode_fin' => 'required|date|after:periode_debut',
            'residences' => 'required|array|min:1',
            'residences.*' => 'exists:cite,id_cite',
            'documents' => 'sometimes|array',
            'documents.*' => 'exists:document,id_document'
        ];
    }

    public function messages(): array
    {
        return [
            'annee_campagne.required' => 'L\'année académique est obligatoire',
            'annee_campagne.regex' => 'Le format doit être AAAA-AAAA (ex: 2025-2026)',
            'annee_campagne.unique' => 'Cette année académique existe déjà',
            'libelle.required' => 'Le libellé de la campagne est obligatoire',
            'libelle.max' => 'Le libellé ne doit pas dépasser 150 caractères',
            'description.max' => 'La description ne doit pas dépasser 1 000 caractères',
            'periode_debut.required' => 'La date de début est obligatoire',
            'periode_fin.required' => 'La date de fin est obligatoire',
            'date_echeance_demande.required' => 'La date d\'échéance des demandes est obligatoire',
            'date_echeance_contrat.required' => 'La date d\'échéance des contrats est obligatoire',
            'date_echeance_demande.after' => 'La date d\'échéance des demandes doit être après la fin de la campagne',
            'date_echeance_contrat.after' => 'La date d\'échéance des contrats doit être après la date d\'échéance des demandes',
            'residences.required' => 'Veuillez sélectionner au moins une résidence',
            'residences.min' => 'Veuillez sélectionner au moins une résidence'
        ];
    }
}