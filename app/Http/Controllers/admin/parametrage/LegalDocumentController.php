<?php

namespace App\Http\Controllers\admin\parametrage;

use App\Http\Controllers\Controller;
use App\Http\Services\LegalDocumentService;
use App\Models\LegalDocument;
use App\Models\LegalDocumentVersion;
use App\Models\LegalSignatory;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class LegalDocumentController extends Controller
{
    public function index()
    {
        return view('admin.parametrage.legal_documents.index', [
            'documents' => LegalDocument::latest('updated_at')->paginate(20),
            'types' => LegalDocument::TYPES,
        ]);
    }

    public function create(Request $request)
    {
        $type = $request->query('type', 'reglement_interieur');
        abort_unless(is_string($type) && array_key_exists($type, LegalDocument::TYPES), 404);
        return $this->editor(new LegalDocument([
            'type' => $type, 'title' => LegalDocument::TYPES[$type], 'version_label' => '1.0',
            'institution' => systemConfig('app_name', config('app.name')),
            'scope_label' => 'Ensemble des résidences',
            'body' => '',
            'signatory_roles' => [],
            'schedule' => [], 'lock_version' => 1, 'status' => 'draft',
        ]));
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        unset($data['lock_version']);
        $document = LegalDocument::create($data + [
            'status' => 'draft', 'created_by' => (string) auth('admin')->id(), 'updated_by' => (string) auth('admin')->id(),
        ]);
        return redirect()->route('admin.parametrage.legal-documents.edit', $document)->with('success', 'Brouillon enregistré.');
    }

    public function edit(LegalDocument $document) { return $this->editor($document); }

    private function editor(LegalDocument $document)
    {
        return response()->view('admin.parametrage.legal_documents.edit', [
            'document' => $document, 'types' => LegalDocument::TYPES,
            'versions' => $document->exists
                ? $document->versions()->orderByDesc('revision')->get(['id', 'legal_document_id', 'revision', 'created_at'])
                : collect(),
        ])->header('Cache-Control', 'private, no-store');
    }

    public function update(Request $request, LegalDocument $document)
    {
        $data = $this->validated($request, $document);
        DB::transaction(function () use ($document, $data) {
            $locked = LegalDocument::whereKey($document->id)->lockForUpdate()->firstOrFail();
            $this->checkVersion($locked, (int) $data['lock_version']);
            $locked->fill($data);
            $locked->status = 'draft';
            $locked->published_version_id = null;
            $locked->lock_version++;
            $locked->updated_by = (string) auth('admin')->id();
            $locked->save();
        });
        return redirect()->route('admin.parametrage.legal-documents.edit', $document)
            ->with('success', 'Brouillon enregistré. Les versions déjà validées restent disponibles dans l’historique.');
    }

    public function publish(Request $request, LegalDocument $document, LegalDocumentService $service)
    {
        $data = $request->validate(['lock_version' => ['required', 'integer', 'min:1']]);
        DB::transaction(function () use ($document, $service, $data) {
            $locked = LegalDocument::whereKey($document->id)->lockForUpdate()->firstOrFail();
            $this->checkVersion($locked, (int) $data['lock_version']);
            if ($locked->status === 'published') return;
            $snapshot = $service->snapshot($locked, true);
            $revision = (int) $locked->versions()->max('revision') + 1;
            $version = $locked->versions()->create([
                'revision' => $revision, 'snapshot' => $snapshot, 'created_by' => (string) auth('admin')->id(),
            ]);
            $locked->update(['status' => 'published', 'published_version_id' => $version->id,
                'lock_version' => $locked->lock_version + 1, 'updated_by' => (string) auth('admin')->id()]);
        });
        return redirect()->route('admin.parametrage.legal-documents.edit', $document)
            ->with('success', 'Version validée : le fichier est conservé dans l’historique.');
    }

    public function preview(LegalDocument $document, LegalDocumentService $service)
    {
        if ($document->file_path) return $this->pdf($document, $service);
        return response()->view('admin.parametrage.legal_documents.preview', [
            'document' => $document, 'snapshot' => $this->currentSnapshot($document, $service),
        ])->header('Cache-Control', 'private, no-store');
    }

    public function pdf(LegalDocument $document, LegalDocumentService $service)
    {
        return $this->pdfResponse($this->currentSnapshot($document, $service), 'document-'.$document->id.'.pdf');
    }

    public function versionPdf(LegalDocument $document, LegalDocumentVersion $version)
    {
        abort_unless($version->legal_document_id === $document->id, 404);
        return $this->pdfResponse($version->snapshot, 'document-'.$document->id.'-revision-'.$version->revision.'.pdf');
    }

    private function currentSnapshot(LegalDocument $document, LegalDocumentService $service): array
    {
        if ($document->status === 'published' && $document->published_version_id) {
            return $document->versions()->findOrFail($document->published_version_id)->snapshot;
        }
        return $service->snapshot($document);
    }

    private function pdfResponse(array $snapshot, string $name)
    {
        if (!empty($snapshot['file_path'])) return app(\App\Http\Services\LegalDocumentService::class)->uploadedResponse($snapshot);
        if (!extension_loaded('gd') && collect($snapshot['signatures'])->contains(fn ($signature) => str_starts_with($signature['image'] ?? '', 'data:image/png'))) {
            throw ValidationException::withMessages(['document' => 'Activez l’extension PHP GD sur le serveur pour générer les PDF contenant des signatures PNG.']);
        }
        return Pdf::loadView('pdf.legal-document', compact('snapshot'))
            ->setPaper('a4', 'portrait')
            ->setOptions(['defaultFont' => 'DejaVu Sans', 'isRemoteEnabled' => false, 'isPhpEnabled' => false])
            ->stream($name)->header('Cache-Control', 'private, no-store');
    }

    private function checkVersion(LegalDocument $document, int $expected): void
    {
        if ($document->lock_version !== $expected) {
            throw ValidationException::withMessages(['document' => 'Ce document a été modifié dans un autre onglet. Rechargez la page avant de continuer.']);
        }
    }

    private function validated(Request $request, ?LegalDocument $document = null): array
    {
        $data = $request->validate([
            'type' => ['required', Rule::in(array_keys(LegalDocument::TYPES))],
            'title' => ['required', 'string', 'max:200'], 'reference' => ['nullable', 'string', 'max:100'],
            'version_label' => ['required', 'string', 'max:30'], 'institution' => ['required', 'string', 'max:200'],
            'scope_label' => ['nullable', 'string', 'max:150'], 'academic_year' => ['nullable', 'string', 'max:30'],
            'effective_date' => ['nullable', 'date_format:Y-m-d'],
            'lock_version' => [$document ? 'required' : 'nullable', 'integer', 'min:1'],
            'file' => [($document?->file_path ? 'nullable' : 'required'), 'file', 'mimes:pdf', 'mimetypes:application/pdf', 'max:2048'],
        ], ['file.required' => 'Téléversez le document PDF.', 'file.mimes' => 'Le document doit être un PDF.', 'file.max' => 'Le document ne doit pas dépasser 2 Mo.']);
        if ($request->hasFile('file') && !str_starts_with(file_get_contents($request->file('file')->getRealPath(), false, null, 0, 5), '%PDF-')) {
            throw ValidationException::withMessages(['file' => 'Le document doit être un PDF valide.']);
        }
        unset($data['file']);
        if ($document) $this->checkVersion($document, (int) $data['lock_version']);
        $data['body'] = ''; $data['body_format'] = 'text'; $data['signatory_roles'] = []; $data['schedule'] = [];
        if ($request->hasFile('file')) {
            $data['file_path'] = upload('private:legal-documents', 'pdf', $request, 'file');
            if (!$data['file_path']) throw ValidationException::withMessages(['file' => 'Impossible d’enregistrer le document.']);
            $data['file_name'] = $request->file('file')->getClientOriginalName();
        }
        return $data;
    }
}