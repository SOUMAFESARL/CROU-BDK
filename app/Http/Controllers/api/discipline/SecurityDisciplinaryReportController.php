<?php
namespace App\Http\Controllers\api\discipline;

use App\Http\Controllers\Controller;
use App\Http\Resources\DisciplinaryReportResource;
use App\Models\DisciplinaryReport;
use App\Models\DisciplinaryReportHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SecurityDisciplinaryReportController extends Controller
{
    public function index(Request $request)
    {
        $reports = DisciplinaryReport::with(['etudiant', 'cite', 'batiment', 'chambre', 'sanction'])
            ->when($request->filled('statut'), fn ($q) => $q->where('statut', $request->input('statut')))
            ->when($request->filled('gravite'), fn ($q) => $q->where('gravite', $request->input('gravite')))
            ->latest('date_incident')->paginate(min(max($request->integer('per_page', 15), 1), 50));
        return DisciplinaryReportResource::collection($reports);
    }

    public function show(DisciplinaryReport $report)
    {
        return new DisciplinaryReportResource($report->load(['etudiant', 'cite', 'batiment', 'chambre', 'historique', 'sanction']));
    }

    public function transition(Request $request, DisciplinaryReport $report)
    {
        abort_unless($request->user('admin')?->hasPermission('discipline.manage'), 403);
        $data = $request->validate([
            'action' => 'required|in:transmettre,prendre_en_charge,retourner,decider,cloturer',
            'commentaire' => 'nullable|required_if:action,retourner,decider|string|max:5000',
            'decision' => 'nullable|required_if:action,decider|string|max:5000',
        ] + \App\Http\Services\DisciplinaryImpactService::decisionRules());
        $transitions = [
            'transmettre' => [['brouillon', 'retourne'], 'transmis'],
            'prendre_en_charge' => [['transmis'], 'en_cours'],
            'retourner' => [['transmis', 'en_cours'], 'retourne'],
            'decider' => [['en_cours'], 'decision_prise'],
            'cloturer' => [['decision_prise'], 'cloture'],
        ];
        $author = $request->user('admin')->getAuthIdentifier();
        $updated = DB::transaction(function () use ($report, $data, $transitions, $author) {
            $locked = DisciplinaryReport::whereKey($report->getKey())->lockForUpdate()->firstOrFail();
            [$allowed, $next] = $transitions[$data['action']];
            if (!in_array($locked->statut, $allowed, true)) {
                throw ValidationException::withMessages(['action' => 'Ce rapport a déjà évolué. Actualisez avant de poursuivre.']);
            }
            $previous = $locked->statut;
            $updates = ['statut' => $next, 'updated_by' => $author];
            if ($next === 'transmis') $updates['transmis_at'] = now();
            if ($next === 'en_cours') $updates['pris_en_charge_at'] = now();
            if ($next === 'decision_prise') {
                $updates['decision_at'] = now();
                $updates['decision'] = $data['decision'];
            }
            $locked->update($updates);
            if ($next === 'decision_prise') app(\App\Http\Services\DisciplinaryImpactService::class)->decider($locked, $data, $author);
            DisciplinaryReportHistory::create([
                'rapport_id' => $locked->getKey(), 'action' => $data['action'],
                'ancien_statut' => $previous, 'nouveau_statut' => $next,
                'commentaire' => $data['commentaire'] ?? $data['decision'] ?? null, 'auteur_id' => $author,
            ]);
            return $locked;
        });
        return (new DisciplinaryReportResource($updated->load(['historique', 'sanction'])))
            ->additional(['message' => 'Le workflow du rapport a été mis à jour.']);
    }
}
