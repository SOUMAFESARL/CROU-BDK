<?php

namespace App\Http\Controllers\admin\parametrage;

use App\Http\Controllers\Controller;
use App\Http\Services\LegalDocumentService;
use App\Models\LegalDocument;
use App\Models\LegalDocumentVersion;
use App\Services\AdminAccess;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class LegalDocumentController extends Controller
{
    public function index(Request $request)
    {
        $type = array_key_exists((string) $request->query('type'), LegalDocument::TYPES) ? (string) $request->query('type') : '';
        $statut = in_array($request->query('statut'), ['published', 'draft'], true) ? (string) $request->query('statut') : '';
        $url = $this->urlSiAutorise($request);

        $documents = LegalDocument::query()
            ->when($type, fn ($query) => $query->where('type', $type))
            ->when($statut === 'published', fn ($query) => $query->where('status', 'published'))
            ->when($statut === 'draft', fn ($query) => $query->where('status', '!=', 'published'))
            ->latest('updated_at')
            ->paginate(10)
            ->withQueryString();

        $parType = LegalDocument::query()
            ->selectRaw("type, count(*) as total, sum(case when status = 'published' then 1 else 0 end) as valides")
            ->groupBy('type')->get()->keyBy('type');
        $total = (int) $parType->sum('total');
        $valides = (int) $parType->sum('valides');

        return Inertia::render('Admin/Parametrage/DocumentsLegaux/Index', [
            'documents' => $documents->through(fn (LegalDocument $document) => [
                'id' => (string) $document->id,
                'title' => $document->title,
                'type' => $document->type,
                'type_label' => LegalDocument::TYPES[$document->type] ?? $document->type,
                'reference' => $document->reference,
                'version_label' => $document->version_label,
                'file_name' => $document->file_name,
                'status' => $document->status === 'published' ? 'published' : 'draft',
                'updated_at' => $document->updated_at?->format('Y-m-d H:i'),
                'urls' => [
                    'edit' => $url('admin.parametrage.legal-documents.edit', $document),
                    'pdf' => $url('admin.parametrage.legal-documents.pdf', $document),
                    'destroy' => $url('admin.parametrage.legal-documents.destroy', $document),
                ],
            ]),
            'types' => collect(LegalDocument::TYPES)->map(fn (string $label, string $code) => [
                'value' => $code,
                'label' => $label,
                'total' => (int) ($parType[$code]->total ?? 0),
                'valides' => (int) ($parType[$code]->valides ?? 0),
                'create' => $url('admin.parametrage.legal-documents.create', ['type' => $code]),
            ])->values(),
            'kpis' => [
                'total' => $total,
                'valides' => $valides,
                'brouillons' => $total - $valides,
                'types_sans_version' => collect(LegalDocument::TYPES)->keys()
                    ->filter(fn (string $code) => (int) ($parType[$code]->valides ?? 0) === 0)->count(),
            ],
            'filters' => ['type' => $type, 'statut' => $statut],
            'urls' => ['index' => route('admin.parametrage.legal-documents.index')],
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

    public function edit(LegalDocument $document)
    {
        return $this->editor($document);
    }

    private function editor(LegalDocument $document)
    {
        $request = request();
        $url = $this->urlSiAutorise($request);
        $existe = $document->exists;

        return Inertia::render('Admin/Parametrage/DocumentsLegaux/Edit', [
            'document' => [
                'id' => $existe ? (string) $document->id : null,
                'type' => $document->type,
                'type_label' => LegalDocument::TYPES[$document->type] ?? $document->type,
                'title' => (string) $document->title,
                'reference' => (string) $document->reference,
                'version_label' => (string) $document->version_label,
                'institution' => (string) $document->institution,
                'scope_label' => (string) $document->scope_label,
                'academic_year' => (string) $document->academic_year,
                'effective_date' => $document->effective_date?->format('Y-m-d') ?? '',
                'lock_version' => (int) $document->lock_version,
                'status' => $document->status === 'published' ? 'published' : 'draft',
                'file_name' => $document->file_path ? ($document->file_name ?: 'document.pdf') : null,
                // Document rédigé avec l'ancien éditeur : il faut téléverser sa version PDF.
                'ancien_editeur' => $existe && !$document->file_path,
                'updated_at' => $existe ? $document->updated_at?->format('Y-m-d H:i') : null,
            ],
            'versions' => $existe
                ? $document->versions()->orderByDesc('revision')->get(['id', 'legal_document_id', 'revision', 'created_at'])
                    ->map(fn (LegalDocumentVersion $version) => [
                        'id' => (string) $version->id,
                        'revision' => (int) $version->revision,
                        'created_at' => $version->created_at?->format('Y-m-d H:i'),
                        'pdf' => $url('admin.parametrage.legal-documents.versions.pdf', [$document, $version]),
                    ])->values()
                : [],
            'urls' => [
                'index' => route('admin.parametrage.legal-documents.index'),
                'submit' => $existe
                    ? $url('admin.parametrage.legal-documents.update', $document)
                    : $url('admin.parametrage.legal-documents.store'),
                'pdf' => $existe && $document->file_path ? $url('admin.parametrage.legal-documents.pdf', $document) : null,
                'publish' => $existe && $document->file_path && $document->status !== 'published'
                    ? $url('admin.parametrage.legal-documents.publish', $document) : null,
                'destroy' => $existe ? $url('admin.parametrage.legal-documents.destroy', $document) : null,
            ],
        ])->toResponse($request)->header('Cache-Control', 'private, no-store');
    }

    /**
     * URL d'une route admin, ou null si l'administrateur connecté n'y a pas accès (remplace `@adminroute`).
     */
    private function urlSiAutorise(Request $request): \Closure
    {
        $access = app(AdminAccess::class);
        $admin = $request->user('admin');

        return fn (string $route, mixed $parametres = []) => $access->allows($admin, $route) ? route($route, $parametres) : null;
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

    /**
     * Retire le document : il disparaît de l'espace des résidents, avec son historique et ses fichiers PDF.
     */
    public function destroy(LegalDocument $document)
    {
        $fichiers = DB::transaction(function () use ($document) {
            $locked = LegalDocument::whereKey($document->id)->lockForUpdate()->firstOrFail();
            $fichiers = $locked->versions()->get()
                ->map(fn (LegalDocumentVersion $version) => $version->snapshot['file_path'] ?? null)
                ->push($locked->file_path);
            $locked->versions()->delete();
            $locked->delete();

            return $fichiers;
        });

        // Fichiers privés propres à ce document (chaque téléversement a son propre chemin).
        $chemins = $fichiers->filter(fn ($chemin) => is_string($chemin) && str_starts_with($chemin, 'legal-documents/') && !str_contains($chemin, '..'))
            ->unique()->values()->all();
        if ($chemins) Storage::disk('local')->delete($chemins);

        return redirect()->route('admin.parametrage.legal-documents.index')
            ->with('success', 'Document retiré : il n’est plus disponible pour les résidents.');
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