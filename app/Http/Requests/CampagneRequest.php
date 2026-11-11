<?php

namespace App\Http\Requests;

use App\Http\Services\CampagneService;
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
        return [
            'annee_campagne' => 'required|string|regex:/^\d{4}-\d{4}$/',
            'type' => ['required', Rule::in(array_keys(CampagneService::TYPES))],
            'libelle' => 'required|string|max:150',
            'description' => 'nullable|string|max:1000',
            'periode_debut' => 'required|date|before:periode_fin',
            'periode_fin' => 'required|date|after:periode_debut',
            'residences' => 'required|array|min:1',
            'residences.*' => 'exists:cite,id_cite',
            'documents' => 'sometimes|array',
            'documents.*' => 'exists:document,id_document',
        ];
    }

    public function messages(): array
    {
        return [
            'annee_campagne.required' => 'L\'année académique est obligatoire',
            'annee_campagne.regex' => 'Le format doit être AAAA-AAAA (ex: 2025-2026)',
            'type.required' => 'Choisissez le type de campagne',
            'type.in' => 'Le type de campagne choisi n\'est pas valide',
            'libelle.required' => 'Le libellé de la campagne est obligatoire',
            'libelle.max' => 'Le libellé ne doit pas dépasser 150 caractères',
            'description.max' => 'La description ne doit pas dépasser 1 000 caractères',
            'periode_debut.required' => 'La date de début est obligatoire',
            'periode_fin.required' => 'La date de fin est obligatoire',
            'residences.required' => 'Veuillez sélectionner au moins une résidence',
            'residences.min' => 'Veuillez sélectionner au moins une résidence',
        ];
    }
}
