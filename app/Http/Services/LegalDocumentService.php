<?php

namespace App\Http\Services;

use App\Models\LegalDocument;
use App\Models\LegalSignatory;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class LegalDocumentService
{
    public const VARIABLES = [
        '{{etablissement}}' => 'Établissement',
        '{{residence}}' => 'Résidence ou périmètre',
        '{{annee_universitaire}}' => 'Année universitaire',
        '{{date_application}}' => 'Date d’application',
        '{{lieu}}' => 'Lieu',
        '{{contact}}' => 'Contact',
    ];

    public function uploadedResponse(array $snapshot)
    {
        $path = $snapshot['file_path'] ?? '';
        abort_unless(str_starts_with($path, 'legal-documents/') && !str_contains($path, '..') && Storage::disk('local')->exists($path), 404);
        return response()->file(Storage::disk('local')->path($path), [
            'Content-Type' => 'application/pdf', 'Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff',
        ]);
    }
    public function signatureData(?LegalSignatory $signatory): ?string
    {
        if (!$signatory?->is_active || !$signatory->full_name || !$signatory->signature_path) return null;
        $path = $signatory->signature_path;
        if (!str_starts_with($path, 'legal-signatures/') || str_contains($path, '..')) return null;
        $disk = Storage::disk('local');
        if (!$disk->exists($path)) return null;
        $bytes = $disk->get($path);
        $info = @getimagesizefromstring($bytes);
        if (!$info || !in_array($info['mime'], ['image/png', 'image/jpeg'], true)) return null;
        return 'data:'.$info['mime'].';base64,'.base64_encode($bytes);
    }

    public function snapshot(LegalDocument $document, bool $final = false): array
    {
        if ($document->file_path) {
            if (!Storage::disk('local')->exists($document->file_path)) {
                throw ValidationException::withMessages(['document' => 'Le fichier téléversé est introuvable. Téléversez-le à nouveau.']);
            }
            return [
                'title' => $document->title, 'type_label' => LegalDocument::TYPES[$document->type],
                'reference' => $document->reference, 'version_label' => $document->version_label,
                'institution' => $document->institution, 'scope_label' => $document->scope_label,
                'academic_year' => $document->academic_year, 'effective_date' => $document->effective_date?->format('d/m/Y'),
                'location' => $document->location, 'contact' => $document->contact,
                'body' => '', 'body_format' => 'text', 'schedule' => [], 'signatures' => [],
                'file_path' => $document->file_path, 'file_name' => $document->file_name,
                'draft' => !$final, 'validated_at' => $final ? now()->format('d/m/Y H:i') : null,
            ];
        }
        if ($final) throw ValidationException::withMessages(['document' => 'Téléversez un PDF avant de valider ce document.']);
        $values = [
            '{{etablissement}}' => $document->institution,
            '{{residence}}' => $document->scope_label,
            '{{annee_universitaire}}' => $document->academic_year,
            '{{date_application}}' => $document->effective_date?->format('d/m/Y'),
            '{{lieu}}' => $document->location,
            '{{contact}}' => $document->contact,
        ];
        $errors = [];
        foreach ($values as $key => $value) {
            if (str_contains($document->body, $key) && blank($value)) {
                $errors[] = 'Renseignez « '.self::VARIABLES[$key].' » avant de valider.';
            }
        }
        $isHtml = $document->body_format === 'html';
        $body = strtr($document->body, array_map(fn ($value) => $isHtml
            ? e(filled($value) ? (string) $value : '[À renseigner]')
            : (filled($value) ? (string) $value : '[À renseigner]'), $values));
        if ($isHtml) $body = LegalDocumentHtml::clean($body);
        if (preg_match('/\{\{[^}]*\}\}/u', $body)) $errors[] = 'Le contenu contient une variable inconnue.';
        if (blank($document->body)) $errors[] = 'Rédigez le contenu du document.';
        if (!$document->effective_date) $errors[] = 'Renseignez la date d’application.';
        if (blank($document->reference)) $errors[] = 'Renseignez la référence du document.';
        if ($document->type === 'calendrier_visites' && empty($document->schedule)) $errors[] = 'Ajoutez au moins une visite ou intervention au calendrier.';

        $configured = LegalSignatory::get()->keyBy('role_code');
        $signatures = [];
        foreach (LegalSignatory::ROLES as $code => $label) {
            if (!in_array($code, $document->signatory_roles ?? [], true)) continue;
            $signatory = $configured->get($code);
            $image = $this->signatureData($signatory);
            if (!$image) $errors[] = 'Signature active et nom requis pour : '.$label.'.';
            $signatures[] = ['role' => $label, 'name' => $signatory?->full_name, 'image' => $image];
        }
        if (!$signatures) $errors[] = 'Sélectionnez au moins un signataire.';
        if ($final && $errors) throw ValidationException::withMessages(['document' => $errors]);

        return [
            'title' => $document->title, 'type_label' => LegalDocument::TYPES[$document->type],
            'reference' => $document->reference, 'version_label' => $document->version_label,
            'institution' => $document->institution, 'scope_label' => $document->scope_label,
            'academic_year' => $document->academic_year, 'location' => $document->location,
            'contact' => $document->contact, 'effective_date' => $document->effective_date?->format('d/m/Y'),
            'body' => $body, 'body_format' => $isHtml ? 'html' : 'text', 'schedule' => $document->schedule ?? [], 'signatures' => $signatures,
            'draft' => !$final, 'validated_at' => $final ? now()->format('d/m/Y H:i') : null,
        ];
    }
}
