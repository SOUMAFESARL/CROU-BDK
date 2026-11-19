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
                $r.'priorite_boursiers' => 'required_with:regles|boolean',
            ];
        }

        $communes = $actions + [
            'regles.calendrier.date_liste_provisoire' => 'nullable|date',
            'regles.calendrier.delai_completude_jours' => 'required_with:regles|integer|min:0|max:60',
            'regles.recevabilite.internationaux' => 'required_with:regles|boolean',
            'regles.conditions.caution_mois' => 'required_with:regles|integer|min:0|max:'.C::MOIS_CONDITIONS_MAX,
            'regles.conditions.avance_mois' => 'required_with:regles|integer|min:0|max:'.C::MOIS_CONDITIONS_MAX,
            'regles.classement.departage' => 'required_with:regles|array|min:1|max:'.count(C::DEPARTAGE[$jeu]),
            'regles.classement.departage.*' => ['distinct', Rule::in(C::DEPARTAGE[$jeu])],
            'regles.classement.k_liste_elargie' => 'required_with:regles|integer|min:1|max:5',
            'places' => 'sometimes|array',
            'places.*.cite_id' => 'required|string',
            'places.*.places' => 'nullable|integer|min:0|max:100000',
        ];

        if ($jeu === 'admission') {
            // Un mérite par public (note du bac sur 400 en Licence 1, crédits sur 60 de la Licence 2 au doctorat), chacun
            // avec son seuil de rejet, obligatoire quand le mérite est retenu.
            $merites = [];
            foreach (C::MERITE_ADMISSION as $code) {
                $m = "regles.admission.merite.{$code}.";
                $merites += [
                    $m.'actif' => 'required_with:regles|boolean',
                    $m.'seuil' => "nullable|required_if_accepted:{$m}actif|numeric|min:0|max:".C::SEUIL_MAX_ADMISSION[$code],
                ];
            }

            return $communes + $merites + [
                'regles.admission.ville_bac' => 'required_with:regles|string|max:100',
            ];
        }

        $r = 'regles.readmission.';

        return $communes + [
            $r.'loyers.actif' => 'required_with:regles|boolean',
            $r.'loyers.mois_exiges' => 'required_with:regles|integer|min:1|max:12',
            // Crédits exigés : pourcentage pour toutes les filières, seuil propre à une filière (école sur concours : C1).
            $r.'credits.actif' => 'required_with:regles|boolean',
            $r.'credits.seuil_defaut' => 'required_with:regles|integer|min:0|max:100',
            $r.'credits.filieres' => 'sometimes|array',
            $r.'credits.filieres.*.filiere_id' => 'required|distinct|exists:filiere,id_filiere',
            $r.'credits.filieres.*.seuil' => 'required|integer|min:0|max:100',
            $r.'credits.filieres.*.concours' => 'sometimes|boolean',
            $r.'conduite.actif' => 'required_with:regles|boolean',
            // Note de conduite minimale, bornée par la note initiale du paramétrage des sanctions.
            $r.'conduite.note_min' => 'nullable|required_if_accepted:'.$r.'conduite.actif|numeric|decimal:0,2|min:0|max:'.app(\App\Http\Services\CatalogueSanctions::class)->noteInitiale(),
            $r.'age_actif' => 'required_with:regles|boolean',
            $r.'report_places' => ['required_with:regles', Rule::in(C::REPORT_PLACES)],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if (! $this->has('regles') && ! $this->has('places')) {
                $validator->errors()->add('criteres', 'Aucun critère à enregistrer.');
            }
            if ($this->jeu() === 'admission' && $this->has('regles')
                && ! collect(C::MERITE_ADMISSION)->contains(fn ($code) => $this->boolean("regles.admission.merite.{$code}.actif"))) {
                $validator->errors()->add('regles.admission.merite', 'Retenez au moins un critère de mérite.');
            }
        });
    }

    public function attributes(): array
    {
        $r = 'regles.readmission.';

        return [
            'regles.calendrier.date_liste_provisoire' => 'date de génération de la liste provisoire',
            'regles.calendrier.delai_completude_jours' => 'délai de complétude',
            $r.'credits.seuil_defaut' => 'pourcentage de crédits exigé',
            $r.'credits.filieres.*.filiere_id' => 'filière',
            $r.'credits.filieres.*.seuil' => 'pourcentage de crédits de la filière',
            'regles.rang_filiere.n' => 'nombre de premiers par filière',
            'regles.clubs.n' => 'nombre de premiers des clubs et associations',
            'regles.cas_sociaux.taux' => 'part des cas sociaux',
            'regles.comite.places' => 'places du comité des résidents',
            'regles.age_max.*.niveau' => 'niveau d’études',
            'regles.age_max.*.age' => 'âge maximum',
            'regles.priorite_boursiers' => 'priorité aux boursiers',
            'regles.admission.merite.moyenne_bac_desc.seuil' => 'note minimale au baccalauréat',
            'regles.admission.merite.credits_desc.seuil' => 'nombre minimum de crédits',
            'regles.conditions.caution_mois' => 'nombre de mois de caution',
            'regles.conditions.avance_mois' => 'nombre de mois d’avance',
            'regles.admission.ville_bac' => 'ville de référence du baccalauréat',
            $r.'loyers.mois_exiges' => 'mois de loyers exigés',
            $r.'conduite.note_min' => 'note de conduite minimale',
            'regles.classement.k_liste_elargie' => 'coefficient de la liste élargie',
            'regles.classement.departage' => 'ordre de départage',
            'regles.classement.departage.*' => 'critère de départage',
            'places.*.places' => 'nombre de places',
        ];
    }
}
