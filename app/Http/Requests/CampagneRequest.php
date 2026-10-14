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
            'description' => 'required|string|min:10|max:1000',
            'periode_debut' => 'required|date|before:periode_fin',
            'periode_fin' => 'required|date|after:periode_debut',
            'date_echeance_demande' => 'required|date|after:periode_fin',
            'date_echeance_contrat' => 'required|date|after:date_echeance_demande',
            //'new_bachelier' => 'sometimes|boolean',
            'is_participate' => 'sometimes|boolean',
            'admission' => 'sometimes|boolean',
            'is_discipline' => 'sometimes|boolean',
            'readmission' => 'sometimes|boolean',
            'pmr' => 'sometimes|boolean',
            'internationnaux' => 'sometimes|boolean',
            'moyenne_min' => 'nullable|numeric|min:0|max:20',
            'redoublement' => 'sometimes|boolean',
            'distance_min' => 'nullable|numeric|min:0',
            'age_L1_max' => 'nullable|integer|min:0|max:40',
            'age_L2_max' => 'nullable|integer|min:0|max:40|gt:age_L1_max',
            'age_L3_max' => 'nullable|integer|min:0|max:40|gt:age_L2_max',
            'age_M1_max' => 'nullable|integer|min:0|max:40|gt:age_L3_max',
            'age_M2_max' => 'nullable|integer|min:0|max:40|gt:age_M1_max',
            'age_D1_max' => 'nullable|integer|min:0|max:40|gt:age_M2_max',
            'age_D2_max' => 'nullable|integer|min:0|max:40|gt:age_D1_max',
            'age_D3_max' => 'nullable|integer|min:0|max:40|gt:age_D2_max',
            'pourcentage_validate'=> 'nullable|integer|min:0|max:100',
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
            'description.required' => 'La description est obligatoire',
            'description.min' => 'La description doit contenir au moins 10 caractères',
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