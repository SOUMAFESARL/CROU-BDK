<?php

namespace App\Http\Controllers\api\etudiant;

use App\Http\Controllers\Controller;
use App\Models\{AssignationChambre, EtatDesLieux};
use App\Repositories\Interfaces\EtatDesLieuxRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InspectionController extends Controller
{
    public function index(Request $request)
    {
        $data = $request->validate(['type_etat' => 'sometimes|in:entree,sortie', 'per_page' => 'sometimes|integer|min:1|max:100']);
        $query = $this->query($request)->with(['pieces.elements', 'pieces.images']);
        if (isset($data['type_etat'])) $query->where('type_etat', $data['type_etat']);
        return $this->response($query->latest('date_etat')->orderByDesc('etat_des_lieux_id')
            ->paginate($data['per_page'] ?? 15)->withQueryString());
    }

    public function show(Request $request, string $inspection)
    {
        return $this->response($this->query($request)->with(['pieces.elements', 'pieces.images'])->findOrFail($inspection));
    }

    public function latestExit(Request $request)
    {
        return $this->response($this->query($request)->where('type_etat', 'sortie')
            ->with(['pieces.elements', 'pieces.images'])->latest('date_etat')->first());
    }

    public function store(Request $request, EtatDesLieuxRepositoryInterface $repository)
    {
        $data = $this->validated($request);
        $id = (string) $request->user()->getKey();
        $result = DB::transaction(function () use ($id, $data, $repository) {
            $assignment = AssignationChambre::actives()->where('etudiant_id', $id)
                ->with('chambre')->latest('date_assignation')->lockForUpdate()->first();
            abort_unless($assignment?->chambre, 422, 'Aucune chambre active.');
            $existing = EtatDesLieux::where('id_etudiant', $id)->where('id_chambre', $assignment->chambre_id)
                ->where('type_etat', 'entree')->lockForUpdate()->first();
            abort_if($existing !== null, 409, "Un état des lieux d'entrée existe déjà. Utilisez sa route de modification.");
            return $repository->create(array_merge($data, [
                'id_etudiant' => $id, 'id_chambre' => $assignment->chambre_id,
                'id_batiment' => $assignment->chambre->batiment_id, 'id_cite' => $assignment->chambre->cite_id,
                'type_etat' => 'entree', 'created_by' => $id,
            ]));
        });
        return $this->response($result, 201);
    }

    public function update(Request $request, string $inspection, EtatDesLieuxRepositoryInterface $repository)
    {
        // Authorize before validating or processing any uploaded image.
        $this->query($request)->findOrFail($inspection);
        $data = $this->validated($request);
        $result = DB::transaction(function () use ($request, $inspection, $data, $repository) {
            $edl = $this->query($request)->lockForUpdate()->findOrFail($inspection);
            abort_unless($edl->type_etat === 'entree', 422, 'La sortie peut uniquement être approuvée.');
            abort_if((bool) $edl->signature_crou, 409, 'Cet état des lieux est signé par le CROU et verrouillé.');
            return $repository->update($inspection, $data);
        });
        return $this->response($result);
    }

    public function approve(Request $request, string $inspection)
    {
        $exitOnly = $request->routeIs('api.student.inspections.exit.approve');
        $this->query($request)->when($exitOnly, fn ($query) => $query->where('type_etat', 'sortie'))->findOrFail($inspection);
        $data = $request->validate([
            'signature_etudiant' => ['required', 'string', 'max:3000000', $this->signatureRule()],
            'signature_crou' => 'prohibited', 'pieces' => 'prohibited', 'pieces_json' => 'prohibited',
            'type_etat' => 'prohibited', 'date_etat' => 'prohibited',
            'observation_generale' => 'prohibited', 'nbre_cle_recus' => 'prohibited',
        ]);
        $edl = DB::transaction(function () use ($request, $inspection, $data, $exitOnly) {
            $edl = $this->query($request)->when($exitOnly, fn ($query) => $query->where('type_etat', 'sortie'))->lockForUpdate()->findOrFail($inspection);
            if ($edl->type_etat === 'sortie') {
                abort_unless((bool) $edl->signature_crou, 422, 'Le CROU doit signer cet état des lieux en premier.');
            } else {
                abort_if((bool) $edl->signature_crou, 409, 'Cet état des lieux est signé par le CROU et verrouillé.');
            }
            abort_if((bool) $edl->signature_etudiant, 409, 'Cet état des lieux est déjà approuvé.');
            $edl->update(['signature_etudiant' => $data['signature_etudiant'], 'updated_by' => $request->user()->getKey()]);
            return $edl;
        });
        return $this->response($edl);
    }

    private function query(Request $request)
    {
        return EtatDesLieux::where('id_etudiant', $request->user()->getKey());
    }

    private function validated(Request $request): array
    {
        if ($request->has('pieces_json') && !$request->has('pieces')) {
            $request->validate(['pieces_json' => 'required|json']);
            $request->merge(['pieces' => json_decode($request->input('pieces_json'), true)]);
        }
        $data = $request->validate([
            'type_etat' => 'sometimes|in:entree', 'signature_crou' => 'prohibited',
            'date_etat' => 'sometimes|date', 'nbre_cle_recus' => 'sometimes|integer|min:0|max:100',
            'observation_generale' => 'nullable|string|max:10000',
            'signature_etudiant' => ['sometimes', 'nullable', 'string', 'max:3000000', $this->signatureRule()],
            'pieces' => 'sometimes|array|min:1|max:30',
            'pieces.*' => 'array:name_piece,ordre,elements,photos',
            'pieces.*.name_piece' => 'required|string|max:255', 'pieces.*.ordre' => 'sometimes|integer|min:0',
            'pieces.*.elements' => 'sometimes|array|max:100',
            'pieces.*.elements.*' => 'array:name_element,etat,observation,ordre,photo',
            'pieces.*.elements.*.name_element' => 'required|string|max:255',
            'pieces.*.elements.*.etat' => 'required|in:bon,moyen,mauvais',
            'pieces.*.elements.*.observation' => 'nullable|string|max:2000',
            'pieces.*.elements.*.photo' => 'nullable|string|max:2800000',
            'pieces.*.elements.*.ordre' => 'sometimes|integer|min:0',
            'pieces.*.photos' => 'sometimes|array|max:6', 'pieces.*.photos.*' => 'string|max:2800000',
        ]);
        foreach ($data['pieces'] ?? [] as $piece) {
            foreach ($piece['elements'] ?? [] as $element) {
                if ($element['etat'] === 'mauvais' && blank($element['observation'] ?? null)) {
                    throw ValidationException::withMessages(['pieces' => 'Un commentaire est obligatoire pour un élément en mauvais état.']);
                }
            }
        }
        return $data;
    }

    private function signatureRule(): \Closure
    {
        return function ($attribute, $value, $fail) {
            if (!preg_match('#^data:image/(png|jpeg|jpg);base64,(.+)$#s', $value, $match)) {
                $fail('Signature PNG ou JPEG encodée en base64 attendue.');
                return;
            }
            $bytes = base64_decode($match[2], true);
            $info = $bytes === false ? false : @getimagesizefromstring($bytes);
            if (!$info || !in_array($info['mime'], ['image/png', 'image/jpeg'], true)
                || strlen($bytes) > 2 * 1024 * 1024 || $info[0] * $info[1] > 4000000) {
                $fail('Image de signature invalide ou trop volumineuse.');
            }
        };
    }

    private function describe(EtatDesLieux $inspection): array
    {
        $inspection->loadMissing('pieces.elements', 'pieces.images');
        $data = $inspection->toArray();
        $exit = $inspection->type_etat === 'sortie';
        $data['can_edit'] = !$exit && !$inspection->signature_crou;
        $data['can_approve'] = !$inspection->signature_etudiant && ($exit ? (bool) $inspection->signature_crou : !$inspection->signature_crou);
        $data['approval_status'] = $inspection->signature_crou && $inspection->signature_etudiant
            ? 'approved' : ($exit ? ($inspection->signature_crou ? 'pending_student' : 'pending_crou')
                : ($inspection->signature_etudiant ? 'pending_crou' : 'pending_student'));
        $data['pdf_url'] = route('api.student.documents.inventory', $inspection->getKey());
        $data['approval_url'] = route('api.student.inspections.approve', $inspection->getKey());
        return $data;
    }

    private function response($data, int $status = 200)
    {
        if ($data instanceof \Illuminate\Pagination\AbstractPaginator) {
            $data->through(fn (EtatDesLieux $inspection) => $this->describe($inspection));
        } elseif ($data instanceof EtatDesLieux) {
            $data = $this->describe($data);
        }
        return response()->json(['success' => true, 'data' => $data], $status)->header('Cache-Control', 'private, no-store');
    }
}
