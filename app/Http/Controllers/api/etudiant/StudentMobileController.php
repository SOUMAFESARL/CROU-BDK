<?php

namespace App\Http\Controllers\api\etudiant;

use App\Http\Controllers\Controller;
use App\Models\AssignationChambre;
use App\Models\DepartureRequest;
use App\Models\OrdreTravail;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class StudentMobileController extends Controller
{

    public function departureRequests(Request $request, ?string $etudiant_id = null): JsonResponse
    {
        $studentId = $this->studentId($request);
        abort_if($etudiant_id !== null && $etudiant_id !== $studentId, 404);
        $items = DepartureRequest::with(['chambre', 'settlement'])
            ->where('etudiant_id', $studentId)
            ->latest('date_demande')
            ->get();

        return $this->success($items);
    }

    public function submitDepartureRequest(Request $request): JsonResponse
    {
        $minimumDate = today()->addDays(15)->toDateString();
        $data = $request->validate([
            'date_sortie_prevue' => ['required', 'date', 'after_or_equal:'.$minimumDate],
            'motif' => ['required', Rule::in(['fin_etudes', 'demenagement', 'stage', 'raison_personnelle', 'autre'])],
            'observations' => ['nullable', 'string', 'max:2000'],
            'conditions_acceptees' => ['required', 'accepted'],
             'etudiant_id' => ['sometimes', 'string', 'max:255'],
        ]);

        $studentId = $this->studentId($request);
        $assignment = AssignationChambre::actives()
            ->where('etudiant_id', $studentId)
            ->latest('date_assignation')
            ->first();

        if (! $assignment) return $this->error('Aucune chambre active ne permet de déposer une demande de sortie.', 422);
        if (DepartureRequest::where('etudiant_id', $studentId)->where('statut', 'en_attente')->exists()) {
            return $this->error('Vous avez déjà une demande de sortie en attente.', 409);
        }

        $departure = DepartureRequest::create([
            'etudiant_id' => $studentId,
            'assignation_id' => $assignment->id_assignation,
            'chambre_id' => $assignment->chambre_id,
            'date_demande' => today(),
            'date_sortie_prevue' => $data['date_sortie_prevue'],
            'motif' => $data['motif'],
            'observations' => $data['observations'] ?? null,
            'statut' => 'en_attente',
            'created_by' => $studentId,
        ]);

        return $this->success($departure, 'Votre demande de sortie a été enregistrée.', 201);
    }

    public function maintenances(Request $request, ?string $etudiant_id = null): JsonResponse
    {
        $studentId = $this->studentId($request);
        abort_if($etudiant_id !== null && $etudiant_id !== $studentId, 404);
        $filters = $request->validate(['per_page' => 'sometimes|integer|min:1|max:100', 'statut' => 'sometimes|string|max:100']);
        $query = OrdreTravail::where('etudiant_id', $studentId);
        if (isset($filters['statut'])) $query->where('statut', $filters['statut']);
        return $this->success($query->latest()->paginate($filters['per_page'] ?? 15)->withQueryString());
    }

    public function submitMaintenance(Request $request): JsonResponse
    {
        $data = $request->validate([
            'titre' => ['required', 'string', 'max:255'],
            'etudiant_id' => ['sometimes', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:5000'],
            'categorie' => ['required', Rule::in(['electricite', 'eau', 'sanitaire', 'mobilier', 'securite', 'chauffage', 'climatisation', 'batiment', 'autre'])],
            'priorite' => ['required', Rule::in(['critique', 'elevee', 'moyenne', 'faible'])],
            'type_intervention' => ['required', Rule::in(['corrective', 'preventive', 'urgente'])],
            'equipement_id' => ['nullable', 'string', 'exists:equipements,id_equipement'],
            'date_disponibilite' => ['nullable', 'date', 'after_or_equal:today'],
            'periode_disponibilite' => ['nullable', 'string', 'max:100'],
            'photos' => ['nullable', 'array', 'max:5'],
            'photos.*' => ['image', 'mimes:jpg,jpeg,png', 'max:5120'],
        ]);

        $studentId = $this->studentId($request);
        $assignment = AssignationChambre::with('chambre')->actives()
            ->where('etudiant_id', $studentId)
            ->latest('date_assignation')
            ->first();
        if (! $assignment?->chambre) return $this->error('Aucune chambre active trouvée.', 422);

        $paths = collect($request->file('photos', []))
            ->map(fn ($file) => $file->store('maintenance/'.$studentId, 'public'))
            ->all();

        $ordre = DB::transaction(fn () => OrdreTravail::create(array_merge($data, [
            'etudiant_id' => $studentId,
            'chambre_id' => $assignment->chambre_id,
            'batiment_id' => $assignment->chambre->batiment_id,
            'cite_id' => $assignment->chambre->cite_id,
            'statut' => 'en_attente',
            'photos' => $paths,
            'created_by' => $studentId,
        ])));

        return $this->success($ordre->fresh(['chambre', 'batiment', 'residence']), 'Demande de maintenance soumise avec succès.', 201);
    }

    private function studentId(Request $request): string
    {
        return (string) $request->attributes->get('student')->id_etudiant;
    }

    private function success(mixed $data, ?string $message = null, int $status = 200): JsonResponse
    {
        return response()->json(['success' => true, 'message' => $message, 'data' => $data], $status);
    }

    private function error(string $message, int $status): JsonResponse
    {
        return response()->json(['success' => false, 'message' => $message], $status);
    }
}
