<?php

namespace App\Http\Controllers\etudiant;

use App\Http\Controllers\Controller;
use App\Models\LegalDocument;
use Barryvdh\DomPDF\Facade\Pdf;

class DocumentController extends Controller
{
    private function student()
    {
        return request()->attributes->get('student') ?? auth('etudiant')->user();
    }
    public function personal()
    {
        $id = $this->student()->getKey();
        $receipts = \App\Models\Paiement::where('resident_id', $id)->whereIn('statut_paiement', ['valide', 'rembourse'])->latest('date_paiement')->get();
        $inventories = \App\Models\EtatDesLieux::where('id_etudiant', $id)->latest('date_etat')->get();
        $attachments = \App\Models\DocumentEtudiant::where('etudiant_id', $id)->latest()->get();
        $departures = \App\Models\DepartureRequest::where('etudiant_id', $id)->whereHas('settlement')->latest('date_demande')->get();
        $hasContract = $this->student()->assignationsChambre()->where('statut', 'confirmee')->exists();
        return response()->view('etudiant.personal-documents', compact('receipts', 'inventories', 'attachments', 'departures', 'hasContract'))
            ->header('Cache-Control', 'private, no-store');
    }

    public function receipt(string $id)
    {
        $payment = \App\Models\Paiement::where('resident_id', $this->student()->getKey())
            ->whereIn('statut_paiement', ['valide', 'rembourse'])->findOrFail($id);
        return Pdf::loadView('pdf.student-receipt', ['payment' => $payment, 'student' => $this->student()])
            ->setOptions(['isRemoteEnabled' => false, 'isPhpEnabled' => false])
            ->download('recu-'.$payment->getKey().'.pdf')->header('Cache-Control', 'private, no-store');
    }

    public function inventory(string $id)
    {
        $inventory = \App\Models\EtatDesLieux::where('id_etudiant', $this->student()->getKey())
            ->with(['pieces.elements', 'pieces.images', 'etudiant', 'cite', 'batiment', 'chambre'])->findOrFail($id);
        return app(\App\Http\Services\StudentInventoryPdfService::class)->document($inventory)
            ->stream('etat-des-lieux-'.$inventory->type_etat.'-'.$inventory->getKey().'.pdf')
            ->header('Cache-Control', 'private, no-store');
    }

    public function attachment(string $id)
    {
        $document = \App\Models\DocumentEtudiant::where('etudiant_id', $this->student()->getKey())->findOrFail($id);
        $stored = str_replace('\\', '/', (string) $document->chemin_fichier);
        // Les anciens téléversements utilisent le dossier public, les récents le disque public Laravel.
        $path = parse_url($stored, PHP_URL_PATH);
        $path = is_string($path) ? rawurldecode($path) : $path;
        abort_unless(is_string($path) && $path !== '', 404);
        foreach ([public_path('admin/assets/images/etudiant-document'), storage_path('app/public')] as $root) {
            $root = realpath($root);
            if (!$root) continue;
            $relative = str_contains($path, '/etudiant-document/')
                ? substr($path, strpos($path, '/etudiant-document/') + strlen('/etudiant-document/'))
                : preg_replace('#^/?storage/#', '', ltrim($path, '/'));
            $resolved = realpath($root.DIRECTORY_SEPARATOR.$relative);
            if ($resolved && is_file($resolved) && str_starts_with($resolved, $root.DIRECTORY_SEPARATOR)) {
                $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($resolved);
                if (in_array($mime, ['application/pdf', 'image/jpeg', 'image/png', 'image/gif', 'image/webp'], true)) {
                    return response()->file($resolved, ['Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff']);
                }
                return response()->download($resolved)->header('Cache-Control', 'private, no-store');
            }
        }
        $remote = app(\App\Http\Services\StudentAttachmentLocation::class)->remoteUrl($stored);
        if ($remote) {
            return redirect()->away($remote)->header('Cache-Control', 'private, no-store');
        }
        abort(404, 'Fichier indisponible.');
    }

    public function settlement(string $id)
    {
        $departure = \App\Models\DepartureRequest::where('etudiant_id', $this->student()->getKey())
            ->with(['etudiant', 'chambre.cite', 'chambre.batiment', 'settlement'])->findOrFail($id);
        abort_unless($departure->settlement, 404);
        return Pdf::loadView('admin.hebergement.depart-etat-des-lieux.settlement-pdf', compact('departure'))
            ->setOptions(['isRemoteEnabled' => false, 'isPhpEnabled' => false])
            ->download('decompte-'.$id.'.pdf')->header('Cache-Control', 'private, no-store');
    }

    public function index()
    {
        $documents = LegalDocument::where('status', 'published')->whereNotNull('published_version_id')
            ->with('versions')->orderBy('type')->get()->map(function ($document) {
                $version = $document->versions->firstWhere('id', $document->published_version_id);
                if (!$version || ($version->snapshot['draft'] ?? true)) return null;
                return ['id' => $document->id, 'snapshot' => $version->snapshot];
            })->filter();
        return response()->view('etudiant.documents', compact('documents'))
            ->header('Cache-Control', 'private, no-store');
    }

    public function pdf(LegalDocument $document)
    {
        abort_unless($document->status === 'published' && $document->published_version_id, 404);
        $snapshot = $document->versions()->findOrFail($document->published_version_id)->snapshot;
        abort_if($snapshot['draft'] ?? true, 404);
        if (!empty($snapshot['file_path'])) return app(\App\Http\Services\LegalDocumentService::class)->uploadedResponse($snapshot);
        return Pdf::loadView('pdf.legal-document', compact('snapshot'))
            ->setPaper('a4', 'portrait')
            ->setOptions(['defaultFont' => 'DejaVu Sans', 'isRemoteEnabled' => false, 'isPhpEnabled' => false])
            ->stream('document-'.$document->id.'.pdf')->header('Cache-Control', 'private, no-store');
    }
}
