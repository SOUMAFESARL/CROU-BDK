<?php
namespace App\Http\Services;

use App\Models\EtatDesLieux;
use Barryvdh\DomPDF\Facade\Pdf;

class StudentInventoryPdfService
{
    public function image(?string $stored): ?string
    {
        if (!$stored) return null;
        // Les signatures mobiles sont enregistrées en base64, celles du web sur disque.
        if (str_starts_with($stored, 'data:')) {
            if (strlen($stored) > 3000000 || !preg_match('#^data:image/(png|jpe?g|gif);base64,(.+)$#is', $stored, $matches)) return null;
            $bytes = base64_decode($matches[2], true);
            $mime = $bytes !== false ? (@getimagesizefromstring($bytes)['mime'] ?? null) : null;
            if (!in_array($mime, ['image/png', 'image/jpeg', 'image/gif'], true)) return null;
            return 'data:'.$mime.';base64,'.base64_encode($bytes);
        }
        $root = realpath(storage_path('app/public'));
        if (!$root) return null;
        $relative = preg_replace('#^/?storage/#', '', str_replace('\\', '/', $stored));
        $path = realpath($root.DIRECTORY_SEPARATOR.$relative);
        if (!$path || !is_file($path) || !str_starts_with($path, $root.DIRECTORY_SEPARATOR)) return null;
        $mime = @getimagesize($path)['mime'] ?? null;
        if (!in_array($mime, ['image/jpeg', 'image/png', 'image/gif'], true)) return null;
        return 'data:'.$mime.';base64,'.base64_encode(file_get_contents($path));
    }

    public function document(EtatDesLieux $inventory)
    {
        $photos = [];
        foreach ($inventory->pieces as $piece) {
            foreach ($piece->images as $image) {
                $photos[$image->getKey()] = $this->image($image->chemin_fichier);
            }
        }
        $elementPhotos = [];
        foreach ($inventory->pieces as $piece) {
            foreach ($piece->elements as $element) {
                if ($element->photo) $elementPhotos[$element->getKey()] = $this->image($element->photo);
            }
        }
        $signatures = [
            'crou' => $this->image($inventory->signature_crou),
            'etudiant' => $this->image($inventory->signature_etudiant),
        ];
        $pdf = Pdf::loadView('pdf.student-inventory', compact('inventory', 'photos', 'elementPhotos', 'signatures'))
            ->setPaper('a4', 'portrait')
            ->setOptions(['defaultFont' => 'DejaVu Sans', 'isRemoteEnabled' => false, 'isPhpEnabled' => false]);
        $pdf->render();
        $canvas = $pdf->getDomPDF()->getCanvas();
        $font = $pdf->getDomPDF()->getFontMetrics()->getFont('DejaVu Sans', 'normal');
        $canvas->page_text(420, 805, 'Page {PAGE_NUM} / {PAGE_COUNT}', $font, 11, [.38, .43, .43]);
        return $pdf;
    }
}
