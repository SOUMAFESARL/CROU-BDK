<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Document;

class DocumentEtudiantRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $document = Document::getAllDocuments(1);
        $rules = [
            'etudiant_id' => 'required|exists:etudiants,id_etudiant'
        ];

        if (!$document->isEmpty()) {
            foreach ($document as $documents) {
                // Pour les fichiers, utilisez la validation appropriée
                $rules[$documents->slug] = 'required|file|mimes:pdf,doc,docx,jpg,png|max:2048';
                // Vous pouvez personnaliser les types de fichiers et la taille max
            }
        }

        return $rules;
    }

    public function messages()
    {
        $messages = [
            'etudiant_id.required' => "Le code de l'étudiant est obligatoire",
            'etudiant_id.exists' => "L'étudiant sélectionné n'existe pas",
        ];

        // Ajouter des messages personnalisés pour chaque document
        $document = Document::getAllDocuments(1);
        if (!$document->isEmpty()) {
            foreach ($document as $documents) {
                $messages[$documents->slug . '.required'] = "Le document " . $documents->nom . " est obligatoire";
                $messages[$documents->slug . '.file'] = "Le document " . $documents->nom . " doit être un fichier";
                $messages[$documents->slug . '.mimes'] = "Le document " . $documents->nom . " doit être au format PDF, DOC, DOCX, JPG ou PNG";
                $messages[$documents->slug . '.max'] = "Le document " . $documents->nom . " ne doit pas dépasser 2 Mo";
            }
        }

        return $messages;
    }

    protected function prepareForValidation()
    {
        // Pas besoin de préparer les fichiers ici
        // Les fichiers sont automatiquement disponibles via $request->file()

        $this->merge([
            'statut' => 'en_verification',
            // 'etudiant_id' est déjà présent dans la requête
        ]);
    }
}