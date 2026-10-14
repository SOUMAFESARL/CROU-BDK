<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCampusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $campusId = $this->route('campus');

        return [
            'code_campus' => [
                'required',
                'string',
                'max:50',
                Rule::unique('campus', 'code_campus')->ignore($campusId, 'id_campus'),
            ],
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'adresse' => 'required|string',
            'ville_id' => 'required|exists:villes,id',
            'region_id' => 'required|exists:regions,id',
            'countrie_id' => 'nullable|exists:countries,id',
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('campus', 'code')->ignore($campusId, 'id_campus'),
            ],
            'telephone1' => 'required|string|max:20',
            'telephone2' => 'nullable|string|max:20',
            'telephone3' => 'nullable|string|max:20',
            'email1' => [
                'required',
                'email',
                Rule::unique('campus', 'email1')->ignore($campusId, 'id_campus'),
            ],
            'email2' => 'nullable|email|different:email1',
            'email3' => 'nullable|email|different:email1,email2',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'slogan' => 'nullable|string|max:255',
            'devise' => 'nullable|string|max:255',
            'directeur_code' => 'nullable|exists:users,id_users',
            'capacite_max' => 'nullable|integer|min:1',
            'superficie' => 'nullable|numeric|min:0',
            'date_creation' => 'nullable|date',
            'statut' => 'required|in:actif,inactif,desactive',
        ];
    }

    public function messages(): array
    {
        return [
            'code_campus.required' => 'Le code du campus est obligatoire.',
            'code_campus.unique' => 'Ce code de campus est déjà utilisé.',
            'name.required' => 'Le nom du campus est obligatoire.',
            'adresse.required' => 'L\'adresse est obligatoire.',
            'ville_id.required' => 'La ville est obligatoire.',
            'ville_id.exists' => 'La ville sélectionnée n\'existe pas.',
            'region_id.required' => 'La région est obligatoire.',
            'region_id.exists' => 'La région sélectionnée n\'existe pas.',
            'code.required' => 'Le code est obligatoire.',
            'code.unique' => 'Ce code est déjà utilisé.',
            'telephone1.required' => 'Le téléphone principal est obligatoire.',
            'email1.required' => 'L\'email principal est obligatoire.',
            'email1.email' => 'Veuillez saisir un email valide.',
            'email1.unique' => 'Cet email est déjà utilisé.',
            'email2.email' => 'Veuillez saisir un email valide.',
            'email2.different' => 'L\'email 2 doit être différent de l\'email 1.',
            'email3.email' => 'Veuillez saisir un email valide.',
            'email3.different' => 'L\'email 3 doit être différent des emails 1 et 2.',
            'logo.image' => 'Le logo doit être une image.',
            'logo.mimes' => 'Le logo doit être au format : jpeg, png, jpg, gif ou svg.',
            'logo.max' => 'Le logo ne doit pas dépasser 2 MB.',
            'directeur_code.exists' => 'Le directeur sélectionné n\'existe pas.',
            'capacite_max.integer' => 'La capacité doit être un nombre entier.',
            'capacite_max.min' => 'La capacité doit être supérieure à 0.',
            'superficie.numeric' => 'La superficie doit être un nombre.',
            'superficie.min' => 'La superficie doit être positive.',
            'date_creation.date' => 'La date de création doit être une date valide.',
            'statut.required' => 'Le statut est obligatoire.',
            'statut.in' => 'Le statut doit être : actif, inactif ou desactive.',
        ];
    }

    public function validated($key = null, $default = null)
    {
        $validated = parent::validated($key, $default);

        // Traiter le logo s'il y en a un
        if ($this->hasFile('logo')) {
            $validated['logo'] = $this->file('logo')->store('campus/logos', 'public');
        } else {
            unset($validated['logo']);
        }

        return $validated;
    }
}