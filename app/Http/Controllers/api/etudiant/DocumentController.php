<?php

namespace App\Http\Controllers\api\etudiant;

use App\Http\Controllers\Controller;
use App\Models\{LegalDocument, Paiement, EtatDesLieux, DocumentEtudiant, DepartureRequest, StudentContractApproval};
use Illuminate\Http\Request;

class DocumentController extends Controller
{
    public function index(Request $request)
    {
        $data = $request->validate(['per_page' => 'sometimes|integer|min:1|max:100']);
        $documents = LegalDocument::where('status', 'published')->whereHas('versions', function ($query) {
            $query->whereColumn('legal_document_versions.id', 'legal_documents.published_version_id')
                ->where('snapshot->draft', false);
        })->with('versions')->orderBy('type')->orderBy('id')->paginate($data['per_page'] ?? 15)->withQueryString();
        $documents->through(function ($document) {
            $snapshot = $document->versions->firstWhere('id', $document->published_version_id)->snapshot;
            return ['id' => $document->id, 'type' => $snapshot['type'] ?? $document->type,
                'title' => $snapshot['title'] ?? '', 'reference' => $snapshot['reference'] ?? null,
                'version_label' => $snapshot['version_label'] ?? null,
                'pdf_url' => route('api.student.documents.pdf', $document->id)];
        });
        return response()->json(['success' => true, 'data' => $documents])->header('Cache-Control', 'private, no-store');
    }

    public function personal(Request $request)
    {
        $id = $request->user()->getKey();
        $items = collect();
        foreach (Paiement::where('resident_id', $id)->whereIn('statut_paiement', ['valide', 'rembourse'])->get() as $item) {
            $items->push(['id' => $item->getKey(), 'type' => 'recu', 'title' => $item->numero_paiement,
                'date' => $item->date_paiement?->toISOString(), 'url' => route('api.student.documents.receipt', $item->getKey())]);
        }
        foreach (EtatDesLieux::where('id_etudiant', $id)->get() as $item) {
            $items->push(['id' => $item->getKey(), 'type' => 'etat-des-lieux', 'title' => 'État des lieux '.$item->type_etat,
                'date' => $item->date_etat?->toISOString(), 'url' => route('api.student.documents.inventory', $item->getKey())]);
        }
        foreach (DocumentEtudiant::where('etudiant_id', $id)->get() as $item) {
            $items->push(['id' => $item->getKey(), 'type' => 'justificatif', 'title' => $item->nom_original ?? 'Justificatif',
                'date' => $item->created_at?->toISOString(), 'statut' => $item->statut,
                'url' => route('api.student.documents.attachment', $item->getKey())]);
        }
        foreach (DepartureRequest::where('etudiant_id', $id)->whereHas('settlement')->get() as $item) {
            $items->push(['id' => $item->getKey(), 'type' => 'decompte', 'title' => 'Décompte de sortie',
                'date' => $item->created_at?->toISOString(), 'url' => route('api.student.documents.settlement', $item->getKey())]);
        }
        foreach (StudentContractApproval::where('student_id', $id)->get() as $contract) {
            $items->push(['id' => $contract->getKey(), 'type' => 'contrat', 'title' => 'Contrat de logement',
                'date' => ($contract->approved_at ?? $contract->created_at)?->toISOString(),
                'approved' => $contract->approved_at !== null, 'document_hash' => $contract->document_hash,
                'url' => route('api.student.contracts.pdf', $contract->getKey()),
                'approval_url' => route('api.student.contracts.approve', $contract->getKey())]);
        }
        return response()->json(['success' => true, 'data' => $items->sortByDesc('date')->values()])
            ->header('Cache-Control', 'private, no-store');
    }
}
