<?php

namespace App\Http\Services;

use App\Models\AssignationChambre;
use App\Models\Loyer;
use App\Models\Paiement;
use App\Models\PaiementRepartition;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PaiementAdmissionDirecte
{
    public function enregistrer(AssignationChambre $assignment, Loyer $loyer, int $moisAvance): void
    {
        $caution = (float) $loyer->caution;
        $mensuel = round(max(0, (float) $loyer->montant_total - $caution), 2);
        $date = now();
        $premierMois = $assignment->date_assignation->copy()->startOfMonth();
        $chambre = $assignment->chambre;
        $calendrier = app(\App\Services\CalendrierLoyer::class);
        $datesLoyer = $calendrier->de($assignment->campagne);
        $commun = [
            'resident_id' => $assignment->etudiant_id,
            'contrat_id' => $assignment->getKey(),
            'chambre_id' => $chambre->getKey(),
            'batiment_id' => $chambre->batiment_id,
            'cite_id' => $chambre->cite_id,
        ];
        $paiement = $commun + [
            'loyer_id' => $loyer->getKey(),
            'devise' => 'XOF',
            'methode_paiement' => 'autre',
            'statut_paiement' => 'valide',
            'date_paiement' => $date,
            'valide_par' => auth('admin')->id(),
        ];

        if ($caution > 0) {
            $depot = Paiement::create($paiement + [
                'type_paiement' => 'caution', 'montant' => $caution, 'montant_du' => $caution,
                'mois' => [], 'notes' => 'Caution enregistrée à l’admission directe.',
            ]);
            DB::table('cautions')->insert($commun + [
                'id_caution' => (string) Str::uuid(),
                'montant_initial' => $caution,
                'montant_retenu' => 0,
                'montant_restitue' => 0,
                'statut' => 'encaissement',
                'paiement_encaissement_id' => $depot->getKey(),
                'date_encaissement' => $date->toDateString(),
                'created_by' => auth('admin')->id(),
                'created_at' => $date,
                'updated_at' => $date,
            ]);
        }

        if ($moisAvance > 0 && $mensuel > 0) {
            $periodes = collect(range(0, $moisAvance - 1))
                ->map(fn (int $offset) => $premierMois->copy()->addMonths($offset));
            $avance = Paiement::create($paiement + [
                'type_paiement' => 'loyer', 'montant' => $mensuel * $moisAvance,
                'montant_du' => $mensuel * $moisAvance,
                'mois' => $periodes->map(fn ($periode) => $periode->format('m-Y'))->all(),
                'notes' => 'Mois d’avance enregistrés à l’admission directe.',
            ]);
            foreach ($periodes as $offset => $periode) {
                $mois = $offset === 0 ? $loyer : $loyer->replicate()->fill([
                    'montant_total' => $mensuel,
                    'caution' => 0,
                    'frais_inscription' => 0,
                    'mois_facturation' => $periode->month,
                    'annee_facturation' => $periode->year,
                    'date_limit_paiement' => $calendrier->echeance($datesLoyer, $periode->month, $periode->year),
                ]);
                $this->solder($mois, $mensuel + ($offset === 0 ? $caution : 0), 0);
                PaiementRepartition::create([
                    'paiement_id' => $avance->getKey(),
                    'type' => PaiementRepartition::TYPE_AVANCE,
                    'montant' => $mensuel,
                    'mois' => $periode->month,
                    'annee' => $periode->year,
                    'description' => 'Paiement anticipé - loyer_id '.$mois->getKey(),
                ]);
            }
        } elseif ($caution > 0) {
            $this->solder($loyer, $caution, $mensuel);
        }
    }

    private function solder(Loyer $loyer, float $paye, float $solde): void
    {
        $loyer->fill([
            'total_paye' => $paye,
            'solde_restant' => $solde,
            'statut' => $solde === 0.0 ? 'termine' : 'actif',
            'date_dernier_paiement' => today(),
        ])->save();
    }
}
