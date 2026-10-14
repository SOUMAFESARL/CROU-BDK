<?php
// app/Http/Requests/DemandeRequest.php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DemandeRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
       // dd('gdshvbhsbdazd');
        $rules = [
            'save_as_draft' => 'sometimes|boolean',
            'etudiant_id' => 'required|exists:etudiants,id_etudiant',
            'annee_academique_precedent' => 'required|string|max:9',
            'niveau_precedent' => 'required|exists:niveau_etude,id_niveau_etude',
            'niveau' => 'required|exists:niveau_etude,id_niveau_etude',
            'filiere' => 'required|exists:filiere,id_filiere',
            'moyenne' => 'sometimes',
            'credit_valider' => 'sometimes',
            //'statut_demande' => 'sometimes|in:en_attente,en_cours,validee,rejetee,annulee',
            'cas_social' => 'nullable|string|max:500',
            'boursier' => 'nullable|in:0,1',
            'annee_academique_actuel' =>  'required|string|max:9',
        ];

        // Pour la mise à jour
        if ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
            $rules['etudiant_id'] = 'sometimes|exists:etudiants,id_etudiant';
            $rules['statut_demande'] = 'sometimes|in:en_attente,en_cours,validee,rejetee,annulee';
        }

        return $rules;
    }

    public function messages()
    {
        return [
            'etudiant_id.required' => "Le code de l'étudiant est obligatoire",
            'etudiant_id.exists' => "L'étudiant sélectionné n'existe pas",

            'periode.required' => "La période est obligatoire",
            'statut_demande.in' => "Le statut de la demande est invalide"
        ];
    }

    protected function prepareForValidation()
    {

            $this->merge([
                'date_demande' => now(),
                'statut_demande' => 'En_attente',
                'niveau_etude_actuel_code' => $this->niveau,
                'niveau_etude_precedent_code' => $this->niveau_precedent,
                'filiere_code' => $this->filiere,
                'cas_social' => $this->cas_social,
                'boursier' => $this->boursier,
                'moyenne'=> $this->moyenne,
                'credit_valider' => $this->credit_valider,
            ]);

    }
}