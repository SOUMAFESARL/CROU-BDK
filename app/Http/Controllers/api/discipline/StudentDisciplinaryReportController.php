<?php

namespace App\Http\Controllers\api\discipline;

use App\Http\Controllers\Controller;
use App\Http\Resources\DisciplinaryReportResource;
use App\Models\AssignationChambre;
use App\Models\DisciplinaryReport;
use App\Models\DisciplinaryReportHistory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use App\Http\Services\EtudiantService;

class StudentDisciplinaryReportController extends Controller
{
            protected $etudiantService;


        public function __construct(
            EtudiantService $etudiantService,

        ) {
            $this->etudiantService = $etudiantService;

        }


    public function index(Request $request,$etudiant_id): AnonymousResourceCollection
    {
        $reports = DisciplinaryReport::query()
            ->where('declarant_id', $etudiant_id)
            ->when($request->filled('statut'), fn ($query) => $query->where('statut', $request->string('statut')))
            ->latest('date_incident')
            ->paginate(min(max($request->integer('per_page', 15), 1), 50));

        return DisciplinaryReportResource::collection($reports);
    }

    public function store(Request $request,$etudiant_id): JsonResponse
    {
        $student = $this->etudiantService->getEtudiantDetails($etudiant_id);
        $data = $request->validate([
            'date_incident' => ['required', 'date', 'before_or_equal:now'],
            'nature' => ['required', Rule::in(['tapage', 'violence', 'harcelement', 'degradation', 'vol', 'non_respect_reglement', 'autre'])],
            'lieu' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'min:20', 'max:5000'],
            'temoins' => ['nullable', 'string', 'max:2000'],
            'gravite' => ['required', Rule::in(['faible', 'moyenne', 'grave', 'critique'])],
            'confidentiel' => ['sometimes', 'boolean'],
        ]);

        $assignment = AssignationChambre::actives()->where('etudiant_id', $etudiant_id)
            ->with('chambre')->latest('date_assignation')->first();
        if (! $assignment?->chambre) {
            throw ValidationException::withMessages(['residence' => 'Aucune chambre active n’est associée à votre compte.']);
        }

        $labels = ['tapage' => 'Tapage / nuisance sonore', 'violence' => 'Violence ou menace', 'harcelement' => 'Harcèlement', 'degradation' => 'Dégradation volontaire', 'vol' => 'Vol ou tentative de vol', 'non_respect_reglement' => 'Non-respect du règlement', 'autre' => 'Autre incident'];
        $report = DB::transaction(function () use ($data, $etudiant_id, $assignment, $labels) {
            $report = DisciplinaryReport::create([
                'etudiant_id' => $etudiant_id, 'declarant_id' => $etudiant_id, 'source' => 'etudiant',
                'cite_id' => $assignment->chambre->cite_id, 'batiment_id' => $assignment->chambre->batiment_id,
                'chambre_id' => $assignment->chambre_id, 'date_incident' => $data['date_incident'],
                'nature' => $labels[$data['nature']], 'lieu' => $data['lieu'], 'description' => $data['description'],
                'temoins' => $data['temoins'] ?? null, 'gravite' => $data['gravite'],
                'confidentiel' => (bool) ($data['confidentiel'] ?? false), 'statut' => 'transmis',
                'transmis_at' => now(), 'created_by' => $etudiant_id,
            ]);
            DisciplinaryReportHistory::create(['rapport_id' => $report->id_rapport, 'action' => 'declaration_etudiant', 'nouveau_statut' => 'transmis', 'commentaire' => 'Incident déclaré par API et transmis au Service Sécurité.', 'auteur_id' => $etudiant_id]);

            return $report;
        });

        return (new DisciplinaryReportResource($report->load(['cite', 'batiment', 'chambre', 'historique'])))
            ->additional(['message' => 'Le rapport a été transmis au Service Sécurité.'])
            ->response()->setStatusCode(201);
    }
}
