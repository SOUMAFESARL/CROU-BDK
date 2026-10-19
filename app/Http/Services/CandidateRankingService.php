<?php
namespace App\Http\Services;

use App\Models\Campagne;
use App\Repositories\Interfaces\EtudiantRepositoryInterface;
use Illuminate\Support\Collection;
use RuntimeException;

class CandidateRankingService
{
    public const RULE = 'Provisoire : score d’éligibilité décroissant, date de dépôt croissante, puis identifiant du dossier. Aucun bonus social ou académique.';

    public function __construct(private EtudiantRepositoryInterface $students) {}

    public function rank(Collection $students, Campagne $campaign): Collection
    {
        $ranked = collect();
        $config = \App\Models\CampaignScoring::where('campagne_id', $campaign->getKey())->latest('id')->first();
        $configured = $config && $config->active;
        foreach ($students as $student) {
            // Seule la dernière demande de la campagne concernée fait autorité.
            $request = $student->demandes->where('campagne_id', $campaign->getKey())
                ->sort(fn ($a, $b) => strcmp((string)$b->created_at, (string)$a->created_at)
                    ?: strcmp((string)$b->getKey(), (string)$a->getKey()))->first();
            if (!$request || $request->etat_dossier !== 'confirme') continue;
            $student->setRelation('derniereDemande', $request);
            if ($configured) {
                $scoring = app(CampaignScoringService::class)->calculate($student, $request, $config);
                $score = $scoring['score'];
            } else {
            $evaluation = $this->students->verifierEligibilite($student, [
                'success'=>true, 'data'=>['campagnes'=>collect([$campaign])],
            ]);
            $score = $evaluation['score_global'] ?? null;
            if (!is_numeric($score) || !is_finite((float)$score) || $score < 0 || $score > 100
                || ($evaluation['total_regles'] ?? 0) < 1) {
                throw new RuntimeException('Classement impossible : score non calculable pour le dossier '.$request->getKey().'.');
            }
                $scoring = ['priority'=>1, 'minimum_score'=>0, 'scoring_version'=>null, 'subscores'=>[]];
            }
            $ranked->push([
                'priority'=>$scoring['priority'], 'minimum_score'=>$scoring['minimum_score'],
                'scoring_version'=>$scoring['scoring_version'], 'subscores'=>$scoring['subscores'],
                'student'=>$student, 'demande_id'=>$request->getKey(), 'score'=>(float)$score,
                'date_demande'=>(string)($request->date_demande ?? $request->created_at ?? '9999-12-31'),
            ]);
        }
        return $ranked->sort(fn ($a, $b) => ($a['priority'] <=> $b['priority']) ?: ($b['score'] <=> $a['score'])
            ?: strcmp($a['date_demande'], $b['date_demande'])
            ?: strcmp((string)$a['demande_id'], (string)$b['demande_id']))
            ->values()->map(fn ($entry, $index) => $entry + ['rang'=>$index + 1]);
    }
}
