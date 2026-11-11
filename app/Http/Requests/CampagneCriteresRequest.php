<?php

namespace App\Http\Requests;

use App\Http\Services\Eligibilite\CriteresCampagne as C;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Critères d'un volet d'une campagne (route `{volet}` : admission ou réadmission) : bloc `regles` (S/DAL)
 * et/ou bloc `places` (comité de direction). Mêmes bornes que le schéma zod de l'écran
 * (resources/js/schemas/criteres-campagne.ts). Le droit de saisir chaque bloc est vérifié par le contrôleur
 * (un bloc sans droit est refusé, pas ignoré).
 */
class CampagneCriteresRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    private function volet(): string
    {
        return $this->route('volet') === 'admission' ? 'admission' : 'readmission';
    }

    public function rules(): array
    {
        $volet = $this->volet();
        $communes = [
            'regles' => 'sometimes|array',
            'regles.calendrier.date_liste_provisoire' => 'nullable|date',
            'regles.calendrier.delai_completude_jours' => 'required_with:regles|integer|min:0|max:60',
            'regles.recevabilite.internationaux' => 'required_with:regles|boolean',

            'regles.classement.priorite_boursiers' => ['required_with:regles', Rule::in(C::PRIORITE_BOURSIERS)],
            'regles.classement.source_boursier' => ['required_with:regles', Rule::in(C::SOURCE_BOURSIER)],
            'regles.classement.departage' => 'required_with:regles|array|min:1|max:'.count(C::DEPARTAGE[$volet]),
            'regles.classement.departage.*' => ['distinct', Rule::in(C::DEPARTAGE[$volet])],
            'regles.classement.k_liste_elargie' => 'required_with:regles|integer|min:1|max:5',

            'places' => 'sometimes|array',
            'places.*.cite_id' => 'required|string',
            'places.*.places' => 'nullable|integer|min:0|max:100000',
            'appliquer' => 'sometimes|boolean',
            'motif' => 'nullable|string|max:1000',
        ];

        if ($volet === 'admission') {
            return $communes + [
                'regles.admission.merite' => ['required_with:regles', Rule::in(C::MERITE_ADMISSION)],
                'regles.admission.eloignement.tranches_km' => 'required_with:regles|array|size:2',
                'regles.admission.eloignement.tranches_km.*' => 'integer|min:1|max:2000',
                'regles.admission.handicap_visible' => ['required_with:regles', Rule::in(C::HANDICAP_VISIBLE)],
                'regles.admission.age_max_L1' => 'nullable|integer|min:15|max:60',
            ];
        }

        $r = 'regles.readmission.';

        return $communes + [
            'regles.calendrier.date_controle_prealables' => 'nullable|date',
            $r.'ue_actif' => 'sometimes|boolean',
            $r.'seuil_ue' => 'required_with:regles|integer|min:0|max:100',
            $r.'concours_actif' => 'sometimes|boolean',
            $r.'seuil_concours' => 'required_with:regles|integer|min:0|max:100',
            $r.'filieres_concours' => 'sometimes|array',
            $r.'filieres_concours.*' => 'distinct|exists:filiere,id_filiere',
            $r.'loyers.actif' => 'required_with:regles|boolean',
            $r.'loyers.mois_exiges' => 'required_with:regles|integer|min:1|max:12',
            $r.'loyers.tolerance_fcfa' => 'required_with:regles|integer|min:0|max:10000000',
            ...collect(array_keys(C::CATEGORIES))->mapWithKeys(fn ($code) => [$r."categories.$code.actif" => 'required_with:regles|boolean'])->all(),
            $r.'categories.C2.n' => 'required_with:regles|integer|min:1|max:100',
            $r.'categories.C2.groupe' => ['required_with:regles', Rule::in(C::GROUPES_C2)],
            $r.'categories.C3.n' => 'required_with:regles|integer|min:1|max:100',
            $r.'categories.C4.taux' => 'required_with:regles|integer|min:0|max:100',
            $r.'categories.C4.base' => ['required_with:regles', Rule::in(C::BASES_C4)],
            $r.'categories.C5.prealables' => 'required_with:regles|boolean',
            $r.'report_places' => ['required_with:regles', Rule::in(C::REPORT_PLACES)],
            $r.'age_max' => 'nullable|integer|min:15|max:60',
            ...collect(C::QUOTAS_PLACES)->mapWithKeys(fn ($code) => ["places.*.quotas.$code" => 'nullable|integer|min:0|max:100000'])->all(),
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if (! $this->has('regles') && ! $this->has('places')) {
                $validator->errors()->add('criteres', 'Aucun critère à enregistrer.');

                return;
            }
            if (! $this->has('regles') || $this->volet() !== 'admission') {
                return;
            }
            $tranches = (array) $this->input('regles.admission.eloignement.tranches_km', []);
            if (count($tranches) === 2 && is_numeric($tranches[0]) && is_numeric($tranches[1]) && (int) $tranches[0] >= (int) $tranches[1]) {
                $validator->errors()->add('regles.admission.eloignement.tranches_km', 'Les tranches d’éloignement doivent être croissantes (ex. 100 km puis 300 km).');
            }
        });
    }

    public function attributes(): array
    {
        $r = 'regles.readmission.';

        return [
            'regles.calendrier.date_controle_prealables' => 'date de contrôle des préalables',
            'regles.calendrier.date_liste_provisoire' => 'date de génération de la liste provisoire',
            'regles.calendrier.delai_completude_jours' => 'délai de complétude',
            $r.'seuil_ue' => 'seuil d’UE validées',
            $r.'seuil_concours' => 'seuil des écoles sur concours',
            $r.'filieres_concours.*' => 'filière sur concours',
            $r.'loyers.mois_exiges' => 'mois de loyers exigés',
            $r.'loyers.tolerance_fcfa' => 'tolérance sur les loyers',
            $r.'categories.C2.n' => 'nombre de premiers par filière',
            $r.'categories.C3.n' => 'nombre de premiers par discipline',
            $r.'categories.C4.taux' => 'taux des cas sociaux',
            $r.'age_max' => 'âge maximum',
            'regles.admission.age_max_L1' => 'âge maximum en L1',
            'regles.admission.eloignement.tranches_km.*' => 'tranche d’éloignement',
            'regles.classement.k_liste_elargie' => 'coefficient de la liste élargie',
            'regles.classement.departage' => 'ordre de départage',
            'regles.classement.departage.*' => 'critère de départage',
            'places.*.places' => 'nombre de places',
            'places.*.quotas.C1' => 'quota des écoles sur concours',
            'places.*.quotas.C3' => 'quota des clubs',
            'places.*.quotas.C5' => 'quota du comité des résidents',
        ];
    }
}
