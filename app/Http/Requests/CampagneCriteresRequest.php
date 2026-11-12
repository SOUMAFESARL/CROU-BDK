<?php

namespace App\Http\Requests;

use App\Http\Services\Eligibilite\CriteresCampagne as C;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Critères d'un jeu d'une campagne (route `{volet}` : general, admission ou readmission) : bloc `regles` (S/DAL)
 * et/ou, pour un volet, bloc `places` (comité de direction). Mêmes bornes que les schémas zod de l'écran
 * (resources/js/schemas/criteres-campagne.ts). Le droit de saisir chaque bloc est vérifié par le contrôleur
 * (un bloc sans droit est refusé, pas ignoré).
 */
class CampagneCriteresRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    private function jeu(): string
    {
        $jeu = (string) $this->route('volet');

        return array_key_exists($jeu, C::JEUX) ? $jeu : 'readmission';
    }

    public function rules(): array
    {
        $jeu = $this->jeu();
        $actions = [
            'regles' => 'sometimes|array',
            'appliquer' => 'sometimes|boolean',
        ];

        if ($jeu === 'general') {
            $r = 'regles.';

            return $actions + [
                'places' => 'prohibited',
                $r.'credits.seuil_defaut' => 'required_with:regles|integer|min:0|max:100',
                $r.'credits.filieres' => 'sometimes|array',
                $r.'credits.filieres.*.filiere_id' => 'required|distinct|exists:filiere,id_filiere',
                $r.'credits.filieres.*.seuil' => 'required|integer|min:0|max:100',
                $r.'credits.filieres.*.concours' => 'sometimes|boolean',
                $r.'rang_filiere.actif' => 'required_with:regles|boolean',
                $r.'rang_filiere.n' => 'required_with:regles|integer|min:1|max:100',
                $r.'rang_filiere.groupe' => ['required_with:regles', Rule::in(C::GROUPES_C2)],
                $r.'clubs.actif' => 'required_with:regles|boolean',
                $r.'clubs.n' => 'required_with:regles|integer|min:1|max:100',
                $r.'cas_sociaux.actif' => 'required_with:regles|boolean',
                $r.'cas_sociaux.taux' => 'required_with:regles|integer|min:0|max:100',
                $r.'cas_sociaux.base' => ['required_with:regles', Rule::in(C::BASES_C4)],
                $r.'comite.actif' => 'required_with:regles|boolean',
                $r.'comite.places' => 'required_with:regles|integer|min:0|max:100000',
                $r.'age_max' => 'sometimes|array',
                $r.'age_max.*.niveau' => 'required|string|distinct|exists:niveau_etude,id_niveau_etude',
                $r.'age_max.*.age' => 'nullable|integer|min:15|max:60',
            ];
        }

        $communes = $actions + [
            'regles.calendrier.date_liste_provisoire' => 'nullable|date',
            'regles.calendrier.delai_completude_jours' => 'required_with:regles|integer|min:0|max:60',
            'regles.recevabilite.internationaux' => 'required_with:regles|boolean',
            'regles.classement.departage' => 'required_with:regles|array|min:1|max:'.count(C::DEPARTAGE[$jeu]),
            'regles.classement.departage.*' => ['distinct', Rule::in(C::DEPARTAGE[$jeu])],
            'regles.classement.k_liste_elargie' => 'required_with:regles|integer|min:1|max:5',
            'places' => 'sometimes|array',
            'places.*.cite_id' => 'required|string',
            'places.*.places' => 'nullable|integer|min:0|max:100000',
        ];

        if ($jeu === 'admission') {
            $max = C::SEUIL_MAX_ADMISSION[$this->input('regles.admission.merite')] ?? 360;

            return $communes + [
                'regles.admission.merite' => ['required_with:regles', Rule::in(C::MERITE_ADMISSION)],
                'regles.admission.seuil_actif' => 'required_with:regles|boolean',
                'regles.admission.seuil' => "nullable|required_if_accepted:regles.admission.seuil_actif|numeric|min:0|max:{$max}",
                'regles.admission.age_actif' => 'required_with:regles|boolean',
                'regles.admission.ville_bac' => 'required_with:regles|string|max:100',
            ];
        }

        $r = 'regles.readmission.';

        return $communes + [
            $r.'loyers.actif' => 'required_with:regles|boolean',
            $r.'loyers.mois_exiges' => 'required_with:regles|integer|min:1|max:12',
            $r.'credits_actif' => 'required_with:regles|boolean',
            $r.'conduite.actif' => 'required_with:regles|boolean',
            $r.'age_actif' => 'required_with:regles|boolean',
            $r.'report_places' => ['required_with:regles', Rule::in(C::REPORT_PLACES)],
            'regles.classement.priorite_boursiers' => ['required_with:regles', Rule::in(C::PRIORITE_BOURSIERS)],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if (! $this->has('regles') && ! $this->has('places')) {
                $validator->errors()->add('criteres', 'Aucun critère à enregistrer.');
            }
        });
    }

    public function attributes(): array
    {
        $r = 'regles.readmission.';

        return [
            'regles.calendrier.date_liste_provisoire' => 'date de génération de la liste provisoire',
            'regles.calendrier.delai_completude_jours' => 'délai de complétude',
            'regles.credits.seuil_defaut' => 'pourcentage de crédits exigé',
            'regles.credits.filieres.*.filiere_id' => 'filière',
            'regles.credits.filieres.*.seuil' => 'pourcentage de crédits de la filière',
            'regles.rang_filiere.n' => 'nombre de premiers par filière',
            'regles.clubs.n' => 'nombre de premiers des clubs et associations',
            'regles.cas_sociaux.taux' => 'part des cas sociaux',
            'regles.comite.places' => 'places du comité des résidents',
            'regles.age_max.*.niveau' => 'niveau d’études',
            'regles.age_max.*.age' => 'âge maximum',
            'regles.admission.seuil' => 'seuil minimum',
            'regles.admission.ville_bac' => 'ville de référence du baccalauréat',
            $r.'loyers.mois_exiges' => 'mois de loyers exigés',
            'regles.classement.k_liste_elargie' => 'coefficient de la liste élargie',
            'regles.classement.departage' => 'ordre de départage',
            'regles.classement.departage.*' => 'critère de départage',
            'places.*.places' => 'nombre de places',
        ];
    }
}
