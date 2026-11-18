<?php

namespace App\Http\Requests;

use App\Http\Services\ReadmissionFormulaire;
use App\Models\Etudiant;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Envoi du formulaire web de réadmission (espace étudiant).
 * Mêmes règles que l'API mobile, plus : filière, moyenne et crédits obligatoires, niveau proposé à l'étudiant et pièces demandées par la campagne.
 */
class ReadmissionWebRequest extends FormRequest
{
    /** Champs transmis à ReadmissionService (les fichiers sont traités à part). */
    public const CHAMPS = ['campagne_id', 'niveau', 'moyenne', 'credit_valider', 'boursier', 'cas_social'];

    private ?\Illuminate\Support\Collection $documents = null;

    public function authorize(): bool
    {
        return $this->etudiant() !== null;
    }

    public function etudiant(): ?Etudiant
    {
        return $this->attributes->get('student') ?? auth('etudiant')->user();
    }

    public function rules(): array
    {
        $formulaire = app(ReadmissionFormulaire::class);
        $rules = [
            'campagne_id' => 'required|string|exists:campagne,id_campagne',
            'niveau' => ['required', 'string', 'exists:niveau_etude,id_niveau_etude', function ($attribut, $valeur, $echec) use ($formulaire) {
                $etudiant = $this->etudiant();
                if ($etudiant && ! $formulaire->niveauxProposes($etudiant)->contains(fn ($niveau) => (string) $niveau->getKey() === (string) $valeur)) {
                    $echec('Choisissez un niveau égal ou supérieur à votre dernier niveau d’étude.');
                }
            }],
            // Filière du référentiel, ou saisie libre rattachée/créée par ReferentielLibreService (comme pour l'admission).
            'filiere' => 'required_without:filiere_autre|nullable|exists:filiere,id_filiere',
            'filiere_autre' => 'nullable|string|max:150',
            'moyenne' => 'required|numeric|min:0|max:20',
            'credit_valider' => 'required|numeric|min:0',
            'boursier' => 'nullable|boolean',
            'cas_social' => 'nullable|string|max:2000',
        ];

        // Pièces de la campagne, envoyées sous le slug du document, comme pour la demande d'admission.
        foreach ($this->documentsCampagne() as $document) {
            $rules[$document->slug] = [
                $document->champs_requis ? 'required' : 'nullable',
                'file',
                'max:2048',
                'mimes:'.implode(',', $formulaire->formats($document)),
            ];
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'campagne_id.required' => 'Choisissez la campagne.',
            'niveau.required' => 'Choisissez votre niveau d’étude actuel.',
            'filiere.required_without' => 'Choisissez votre filière.',
            'filiere.exists' => 'La filière choisie n’existe pas.',
            'filiere_autre.max' => 'Le nom de la filière ne doit pas dépasser 150 caractères.',
            'moyenne.required' => 'Saisissez votre moyenne.',
            'credit_valider.required' => 'Saisissez le nombre de crédits validés.',
            ...$this->documentsCampagne()->flatMap(fn ($document) => [
                $document->slug.'.required' => "Le document {$document->name} est obligatoire.",
                $document->slug.'.file' => "Le document {$document->name} n’a pas été reçu correctement. Sélectionnez-le de nouveau.",
                $document->slug.'.uploaded' => "Le document {$document->name} n’a pas pu être envoyé (2 Mo maximum).",
                $document->slug.'.max' => "Le document {$document->name} ne doit pas dépasser 2 Mo.",
                $document->slug.'.mimes' => "Le document {$document->name} doit être au format :values.",
            ])->all(),
        ];
    }

    /** @return \Illuminate\Support\Collection<int, \App\Models\Document> */
    public function documentsCampagne(): \Illuminate\Support\Collection
    {
        $campagne = $this->input('campagne_id');

        return $this->documents ??= app(ReadmissionFormulaire::class)->documentsCampagne(is_string($campagne) ? $campagne : null);
    }

    /** Rattache ou crée la filière saisie librement, une fois la requête validée. */
    protected function passedValidation(): void
    {
        app(\App\Http\Services\ReferentielLibreService::class)->appliquer($this);
    }

    /** Données transmises à ReadmissionService, avec l'identifiant de filière retenu (choisi ou saisi librement). */
    public function donnees(): array
    {
        return $this->safe()->only(self::CHAMPS) + ['filiere' => (string) $this->input('filiere')];
    }

    /** Pièces reçues pour les documents de la campagne, indexées par slug. */
    public function pieces(): array
    {
        return $this->documentsCampagne()
            ->mapWithKeys(fn ($document) => [$document->slug => $this->file($document->slug)])
            ->filter()
            ->all();
    }
}
