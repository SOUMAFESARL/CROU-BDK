<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCiteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $citeId = $this->route('cite');

        return [
            'code_cite' => [
                'required',
                'string',
                'max:50',
                Rule::unique('cite', 'code_cite')->ignore($citeId, 'id_cite'),
            ],
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('cite', 'code')->ignore($citeId, 'id_cite'),
            ],
            'name' => 'required|string|max:255',
            'type_cite_code' => 'required|string|max:50',
            'campus_code' => 'required|string|max:50|exists:campus,code_campus',
            'adresse' => 'required|string',
            'capacite_max' => 'nullable|integer|min:1',
            'nombre_batiments' => 'nullable|integer|min:0',
            'superficie' => 'nullable|numeric|min:0',
            'annee_construction' => 'nullable|integer|min:1900|max:' . date('Y'),
            'responsable_code' => 'nullable|exists:users,id_users',
            'telephone1' => 'required|string|max:20',
            'telephone2' => 'nullable|string|max:20',
            'telephone3' => 'nullable|string|max:20',
            'email1' => [
                'required',
                'email',
                Rule::unique('cite', 'email1')->ignore($citeId, 'id_cite'),
            ],
            'email2' => 'nullable|email|different:email1',
            'longitude' => 'nullable|numeric|between:-180,180',
            'latitude' => 'nullable|numeric|between:-90,90',
            'reglement_interieur_code' => 'nullable|string|max:50',
            'statut' => 'required|in:actif,inactif,desactive',
            'wifi_gratuit' => 'boolean',
            'salle_etude' => 'boolean',
            'infirmerie' => 'boolean',
            'restaurant' => 'boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'code_cite.required' => 'Le code de la cité est obligatoire.',
            'code_cite.unique' => 'Ce code de cité est déjà utilisé.',
            'code.required' => 'Le code est obligatoire.',
            'code.unique' => 'Ce code est déjà utilisé.',
            'name.required' => 'Le nom de la cité est obligatoire.',
            'type_cite_code.required' => 'Le type de cité est obligatoire.',
            'campus_code.required' => 'Le campus est obligatoire.',
            'campus_code.exists' => 'Le campus sélectionné n\'existe pas.',
            'adresse.required' => 'L\'adresse est obligatoire.',
            'telephone1.required' => 'Le téléphone principal est obligatoire.',
            'email1.required' => 'L\'email principal est obligatoire.',
            'email1.email' => 'Veuillez saisir un email valide.',
            'email1.unique' => 'Cet email est déjà utilisé.',
            'email2.email' => 'Veuillez saisir un email valide.',
            'email2.different' => 'L\'email 2 doit être différent de l\'email 1.',
            'capacite_max.integer' => 'La capacité doit être un nombre entier.',
            'capacite_max.min' => 'La capacité doit être supérieure à 0.',
            'nombre_batiments.integer' => 'Le nombre de bâtiments doit être un nombre entier.',
            'nombre_batiments.min' => 'Le nombre de bâtiments doit être positif.',
            'superficie.numeric' => 'La superficie doit être un nombre.',
            'superficie.min' => 'La superficie doit être positive.',
            'annee_construction.integer' => 'L\'année de construction doit être un nombre entier.',
            'annee_construction.min' => 'L\'année de construction doit être supérieure à 1900.',
            'responsable_code.exists' => 'Le responsable sélectionné n\'existe pas.',
            'longitude.numeric' => 'La longitude doit être un nombre.',
            'longitude.between' => 'La longitude doit être entre -180 et 180.',
            'latitude.numeric' => 'La latitude doit être un nombre.',
            'latitude.between' => 'La latitude doit être entre -90 et 90.',
            'statut.required' => 'Le statut est obligatoire.',
            'statut.in' => 'Le statut doit être : actif, inactif ou desactive.',
        ];
    }
}