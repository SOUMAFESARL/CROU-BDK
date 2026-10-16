<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\Maintenancier;

class MaintenancierRequest extends FormRequest
{
    /**
     * Déterminer si l'utilisateur est autorisé à faire cette requête
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Règles de validation
     */
    public function rules(): array
    {
        $id = $this->route('id') ?? $this->route('maintenancier');

        return [
            'name' => ['required', 'string', 'max:255'],
            'tel' => ['required', 'string', 'max:20'],
            'email' => [
                'nullable',
                'email',
                'max:255',
                Rule::unique('maintenanciers', 'email')->ignore($id, 'id_maintenancier')
            ],
            'specialite' => ['required', 'string', Rule::in(Maintenancier::SPECIALITE)],
            'cite_id' => ['nullable', 'exists:cite,id_cite'],
            'notes' => ['nullable', 'string'],
            'adresse' => ['nullable', 'string', 'max:255'],
            'statut' => ['boolean'],
        ];
    }

    /**
     * Messages d'erreur personnalisés
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Le nom du maintenancier est obligatoire',
            'tel.required' => 'Le numéro de téléphone est obligatoire',
            'email.unique' => 'Cet email est déjà utilisé par un autre maintenancier',
            'specialite.required' => 'La spécialité est obligatoire',
            'specialite.in' => 'La spécialité sélectionnée n\'est pas valide',
            'cite_id.exists' => 'La résidence sélectionnée n\'existe pas',
        ];
    }

    /**
     * Préparer les données avant validation
     */
    protected function prepareForValidation(): void
    {
        // Nettoyer le numéro de téléphone
        if ($this->has('tel')) {
            $this->merge([
                'tel' => preg_replace('/[^0-9+]/', '', $this->tel)
            ]);
        }
    }
}