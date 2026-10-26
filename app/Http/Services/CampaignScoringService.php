<?php
namespace App\Http\Services;
use App\Models\{CampaignScoring, Etudiant, DemandeEtudiant};
use RuntimeException;
class CampaignScoringService
{
    public const GROUPS=['social'=>'Situation de bourse','academique'=>'Résultats académiques','geographique'=>'Distance domicile–campus','specifique'=>'PMR confirmé'];
    public const FIELDS=[
        'social'=>['boursier'=>'Boursier','non_boursier'=>'Non-boursier'],
        'academique'=>['tb'=>'Moyenne ≥ 16/20','bien'=>'14 ≤ moyenne < 16','ab'=>'12 ≤ moyenne < 14','passable'=>'10 ≤ moyenne < 12','insuffisant'=>'Moyenne < 10'],
        'geographique'=>['local'=>'Distance < 50 km','proche'=>'50 ≤ distance < 100 km','loin'=>'100 ≤ distance < 200 km','tres_loin'=>'Distance ≥ 200 km'],
        'specifique'=>['pmr'=>'PMR confirmé','autre'=>'Autre situation'],
    ];
    /** Début (km) de chaque tranche de distance au-delà de « local » ; seuils par défaut des barèmes enregistrés sans seuils. */
    public const DISTANCE_THRESHOLDS=['proche'=>50,'loin'=>100,'tres_loin'=>200];
    /** Seuils de distance du barème (par défaut si la version n'en a pas). */
    public static function distanceThresholds(?CampaignScoring $config): array
    {
        $seuils=$config?->distance_thresholds;
        if (!is_array($seuils)) return self::DISTANCE_THRESHOLDS;
        $valeurs=[];
        foreach (self::DISTANCE_THRESHOLDS as $key=>$defaut) $valeurs[$key]=is_numeric($seuils[$key] ?? null) ? (int)$seuils[$key] : $defaut;
        return $valeurs;
    }
    private function distanceKey(float $distance, array $seuils): string
    {
        foreach (array_reverse($seuils, true) as $key=>$seuil) if ($distance >= $seuil) return $key;
        return 'local';
    }
    public function calculate(Etudiant $student, DemandeEtudiant $request, CampaignScoring $config): array
    {
        $values=[];
        foreach ($config->weights as $group=>$weight) {
            if ((float)$weight === 0.0) { $values[$group]=0.0; continue; }
            $key=null;
            if ($group==='social') {
                $boursier=$request->boursier ?? $student->boursier;
                if ($boursier===null) throw new RuntimeException('Statut de bourse manquant pour le dossier '.$request->getKey().'.');
                $key=$boursier ? 'boursier':'non_boursier';
            } elseif ($group==='academique') {
                $value=$request->moyenne;
                if (!is_numeric($value) || $value<0 || $value>20) throw new RuntimeException('Moyenne manquante ou invalide pour le dossier '.$request->getKey().'.');
                $key=$value>=16?'tb':($value>=14?'bien':($value>=12?'ab':($value>=10?'passable':'insuffisant')));
            } elseif ($group==='geographique') {
                $value=$student->distance;
                if (!is_numeric($value) || $value<0) throw new RuntimeException('Distance manquante ou invalide pour le dossier '.$request->getKey().'.');
                $key=$this->distanceKey((float)$value, self::distanceThresholds($config));
            } elseif ($group==='specifique') {
                $key=$student->pmr_confirme ? 'pmr':'autre';
            }
            if ($key===null || !isset($config->points[$group][$key])) throw new RuntimeException('Barème de scoring incomplet.');
            $values[$group]=(float)$config->points[$group][$key];
        }
        $score=0;
        foreach ($values as $group=>$value) $score+=$value*(float)$config->weights[$group]/100;
        return ['score'=>round($score,2),'priority'=>$config->pmr_first && $student->pmr_confirme?0:1,
            'minimum_score'=>$config->minimum_score,'scoring_version'=>$config->id,'subscores'=>$values];
    }
}
