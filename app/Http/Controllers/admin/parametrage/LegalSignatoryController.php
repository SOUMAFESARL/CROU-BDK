<?php

namespace App\Http\Controllers\admin\parametrage;

use App\Http\Controllers\Controller;
use App\Http\Services\LegalDocumentService;
use App\Models\LegalDocument;
use App\Models\LegalSignatory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class LegalSignatoryController extends Controller
{
    public function index()
    {
        return response()->view('admin.parametrage.legal_documents.signatures', [
            'signatories' => LegalSignatory::get()->keyBy('role_code'),
            'roles' => LegalSignatory::ROLES,
            'types' => LegalDocument::TYPES,
        ])->header('Cache-Control', 'private, no-store');
    }

    public function update(Request $request, string $role)
    {
        abort_unless(array_key_exists($role, LegalSignatory::ROLES), 404);
        $data = $request->validateWithBag($role, [
            'full_name' => ['nullable', 'string', 'max:150'],
            'signature' => ['nullable', 'image', 'mimes:png,jpg,jpeg', 'max:2048', 'dimensions:max_width=2000,max_height=1000'],
            'is_active' => ['required', 'boolean'],
            'remove_signature' => ['nullable', 'boolean'],
        ]);
        $existing = LegalSignatory::where('role_code', $role)->first();
        $hasImage = $request->hasFile('signature')
            || (!$request->boolean('remove_signature') && $existing?->signature_path && Storage::disk('local')->exists($existing->signature_path));
        if ($request->boolean('is_active') && (blank($data['full_name'] ?? null) || !$hasImage)) {
            throw ValidationException::withMessages(['signature' => 'Pour activer cette signature, renseignez le nom du responsable et une image PNG ou JPEG.'])->errorBag($role);
        }
        $path = upload('private:legal-signatures', 'png', $request, 'signature');
        if ($request->hasFile('signature') && !$path) {
            throw ValidationException::withMessages(['signature' => 'Impossible d’enregistrer le fichier de signature.'])->errorBag($role);
        }
        $oldPath = null;
        try {
            DB::transaction(function () use ($request, $data, $role, $path, &$oldPath) {
                $signatory = LegalSignatory::where('role_code', $role)->lockForUpdate()->first()
                    ?? new LegalSignatory(['role_code' => $role]);
                $oldPath = $signatory->signature_path;
                $signatory->fill([
                    'full_name' => $data['full_name'] ?? null, 'is_active' => $request->boolean('is_active'),
                    'updated_by' => (string) auth('admin')->id(),
                ]);
                if ($path) $signatory->signature_path = $path;
                elseif ($request->boolean('remove_signature')) $signatory->signature_path = null;
                $signatory->save();
            });
        } catch (\Throwable $e) {
            if ($path) Storage::disk('local')->delete($path);
            throw $e;
        }
        // Validated document versions contain their own immutable image snapshots.
        if ($oldPath && ($path || $request->boolean('remove_signature'))) Storage::disk('local')->delete($oldPath);
        return redirect()->route('admin.parametrage.legal-signatories.index')->with('success', LegalSignatory::ROLES[$role].' : signature enregistrée.');
    }

    public function image(string $role, LegalDocumentService $service)
    {
        abort_unless(array_key_exists($role, LegalSignatory::ROLES), 404);
        $signatory = LegalSignatory::where('role_code', $role)->firstOrFail();
        // The settings page also needs to display a saved but inactive signature.
        $signatory->is_active = true;
        $signatory->full_name = $signatory->full_name ?: 'Aperçu';
        $data = $service->signatureData($signatory);
        abort_unless($data, 404);
        [$meta, $encoded] = explode(',', $data, 2);
        return response(base64_decode($encoded), 200, [
            'Content-Type' => str_contains($meta, 'image/png') ? 'image/png' : 'image/jpeg',
            'Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
