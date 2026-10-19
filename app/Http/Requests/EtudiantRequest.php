<?php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Rules\Etudiant;

class EtudiantRequest extends FormRequest
{
    /** Documents de la campagne visée, chargés une seule fois (règles + messages). */
    private $documentsCampagne = null;

    public function authorize()
    {
        return true; // À modifier selon vos besoins d'autorisation
    }

    public function rules()
    {

        $rules = [
            'save_as_draft' => 'sometimes|boolean',
            // Informations personnelles
            'genre' => 'required|exists:genre,id',
            'name' => 'required|string|max:255',
            'date_naissance' => 'required|date|before:today',
            'lieu_naissance' => 'required|string|max:255',
            'telephone' => 'required|unique:etudiants,telephone1|string|max:20',
          'email' => 'required|email|unique:etudiants,email1',
            'type_piece' => 'required',
            'num_piece' => 'required|unique:etudiants,numero_piece',
            'date_exp_piece' => 'required',
            'matricule' => 'required|unique:etudiants,matricule',
            'groupe_sanguin' => 'sometimes',

            // Informations académiques

            'moyenne' => 'nullable|numeric|min:1|max:500',
            'redoublement' => 'nullable|in:0,1',

            // Situation sociale
            'boursier' => 'nullable|in:0,1',
            'pmr' => 'nullable|in:0,1',
            'cas_social' => 'nullable|string|max:500',

            // Situation géographique
            // Saisie libre possible (valeur absente des référentiels) : rattachée ou créée par ReferentielLibreService.
            'region' => 'required_without:region_autre|nullable|exists:regions,id',
            'region_autre' => 'nullable|string|max:150',
            'ville' => 'required_without:ville_autre|nullable|exists:villes,id',
            'ville_autre' => 'nullable|required_with:region_autre|string|max:150',
            'filiere_autre' => 'nullable|string|max:150',
            'adresse' => 'required|string|max:255',
            'nationalite' => 'required|string|max:100',
            'distance' => 'sometimes',
            'id_campagne' =>'required|exists:campagne,id_campagne',
            'annee_academique_actuel' =>  'required|string|max:9',

            // Préférences
       //     'type_chambre' => 'nullable|exists:type_chambre,id_type_chambre',
        ];

        // Pièces jointes de la campagne, envoyées sous le slug du document.
        // Les pièces obligatoires ne sont exigées que sur le formulaire web : le contrat de l'API mobile reste inchangé.
        $exigerPieces = $this->routeIs('register.post');
        foreach ($this->documentsCampagne() as $document) {
            $rules[$document->slug] = [
                $exigerPieces && $document->champs_requis ? 'required' : 'nullable',
                'file',
                'max:2048',
                'mimes:'.implode(',', $this->formatsAcceptes($document)),
            ];
        }

        return $rules;
    }

    /** @return \Illuminate\Support\Collection<int, \App\Models\Document> */
    private function documentsCampagne()
    {
        if (! is_string($this->id_campagne) || $this->id_campagne === '') {
            return collect();
        }

        return $this->documentsCampagne ??= \App\Models\DocumentCampagne::with('documents')
            ->where('id_campagne', $this->id_campagne)
            ->get()
            ->flatMap(fn ($lien) => $lien->documents)
            ->unique('id_document')
            ->values();
    }

    /** Formats déclarés sur le document, limités à PDF/JPG/PNG (ceux que le stockage accepte). */
    private function formatsAcceptes(\App\Models\Document $document): array
    {
        $formats = collect(preg_split('/[\s,;|]+/', (string) $document->format_accepte))
            ->map(fn ($format) => strtolower(ltrim($format, '.')))
            ->intersect(['pdf', 'jpg', 'jpeg', 'png'])
            ->values()
            ->all();

        return $formats ?: ['pdf', 'jpg', 'jpeg', 'png'];
    }


    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($validator->errors()->isNotEmpty()) return;
            try {
                $existing = app(\App\Http\Services\StudentIdentityResolver::class)->find($this->telephone, $this->num_piece);
                if ($existing) $validator->errors()->add('telephone', 'Un compte existe déjà. Connectez-vous à votre espace étudiant pour déposer une réadmission.');
            } catch (\Illuminate\Validation\ValidationException $exception) {
                $validator->errors()->add('telephone', 'Cette identité nécessite une vérification. Contactez le service Logement.');
            }
        });
    }

    public function messages()
    {
        return [
            'genre.required' => 'La civilité est obligatoire.',
            'genre.exists' => 'La civilité sélectionnée n\'existe pas.',
            'name.required' => 'Le nom et prénom sont obligatoires.',
            'date_naissance.required' => 'La date de naissance est obligatoire.',
            'date_naissance.before' => 'La date de naissance doit être antérieure à aujourd\'hui.',
            'lieu_naissance.required' => 'Le lieu de naissance est obligatoire.',
            'telephone.required' => 'Le numéro de téléphone est obligatoire.',
            'email.required' => 'L\'email est obligatoire.',
            'email.unique' => 'Un compte existe déjà. Connectez-vous à votre espace étudiant.',
            'telephone.unique' => 'Un compte existe déjà. Connectez-vous à votre espace étudiant pour déposer une réadmission.',
            'region.required' => 'La région est obligatoire.',
            'ville.required' => 'La ville est obligatoire.',
            'region.required_without' => 'La région est obligatoire.',
            'ville.required_without' => 'La ville est obligatoire.',
            'ville_autre.required_with' => 'Saisissez le nom de votre ville.',
            'region_autre.max' => 'Le nom de la région ne doit pas dépasser 150 caractères.',
            'ville_autre.max' => 'Le nom de la ville ne doit pas dépasser 150 caractères.',
            'filiere_autre.max' => 'Le nom de la filière ne doit pas dépasser 150 caractères.',
            'adresse.required' => 'L\'adresse est obligatoire.',
            'nationalite.required' => 'La nationalité est obligatoire.',
            'type_piece.required' => 'Le type de piece est obligatoire.',
            'num_piece.unique' => 'Un compte existe déjà avec cette pièce. Connectez-vous à votre espace étudiant pour déposer une réadmission.',
            'num_piece.required' => 'Le numero de piece est obligatoire.',
            'date_exp_piece.required' => 'La date d\'expiration de la piece est obligatoire.',
            'matricule.required' => 'Le numero matricule est obligatoire.',
            'id_campagne.required' => 'L\'identifiant de la campagne est obligatoire.',
            'annee_academique_actuel.required' => 'L\' annee academique actuel est obligatoire.',
            // Pièces jointes de la campagne.
            ...$this->documentsCampagne()->flatMap(fn ($document) => [
                $document->slug.'.required' => "Le document {$document->name} est obligatoire.",
                $document->slug.'.file' => "Le document {$document->name} n’a pas été reçu correctement. Sélectionnez-le de nouveau.",
                $document->slug.'.uploaded' => "Le document {$document->name} n’a pas pu être envoyé (2 Mo maximum).",
                $document->slug.'.max' => "Le document {$document->name} ne doit pas dépasser 2 Mo.",
                $document->slug.'.mimes' => "Le document {$document->name} doit être au format :values.",
            ])->all(),


        ];
    }

    /** Rattache ou crée la filière, la région et la ville saisies librement, une fois la requête validée. */
    protected function passedValidation(): void
    {
        app(\App\Http\Services\ReferentielLibreService::class)->appliquer($this);
    }

    protected function prepareForValidation()
    {
        $this->merge([
            'email1' => $this->email,
            'telephone1' => $this->telephone,
            'regions_id' => $this->region,
            'ville_id' => $this->ville,
            'genre_id' => $this->genre,
            'matricule'=> $this->matricule,
            'distance' => $this->distance,

            'type_piece_id' => $this->type_piece,
            'numero_piece' => $this->num_piece,
            'date_expiration' => $this->date_exp_piece,
            'groupe_sanguin_id' => $this->groupe_sanguin,
           // 'type_chambre_id' => $this->type_chambre,
        ]);
    }
}