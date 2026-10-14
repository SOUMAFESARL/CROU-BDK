<?php

namespace App\Http\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class EdlPhotoService
{
    public static function url(?string $path): ?string
    {
        if (!$path) return null;
        $base = config('app.storage_public') ?: asset('storage');
        return rtrim($base, '/').'/'.ltrim($path, '/');
    }

    public function enregistrer(array $pieces, array $existing = []): array
    {
        $pieces = array_values($pieces);
        $elementPhotos = [];
        foreach ($pieces as $pieceIndex => &$piece) {
            foreach ($piece['elements'] ?? [] as $elementIndex => $element) {
                $photo = $element['photo'] ?? null;
                $elementPhotos[] = [
                    'piece' => $pieceIndex, 'element' => $elementIndex,
                    'photos' => $photo === null || $photo === '' ? [] : [$photo],
                ];
            }
        }
        unset($piece);
        $pieceCount = count($pieces);
        // Validate every image before writing, including the shared total size limit.
        $pieces = array_merge($pieces, $elementPhotos);
        $pending = [];
        $total = 0;
        foreach ($pieces as &$piece) {
            $photos = $piece['photos'] ?? [];
            if (!is_array($photos) || count($photos) > 6) {
                $this->invalid('Maximum 6 photos par pièce.');
            }
            foreach ($photos as &$photo) {
                if (!is_string($photo)) {
                    $this->invalid('Photo invalide.');
                }
                if (in_array($photo, $existing, true)) {
                    continue;
                }
                if (strlen($photo) > 2800000 || !preg_match('#^data:image/(jpeg|png|webp);base64,(.+)$#s', $photo, $match)) {
                    $this->invalid('Utilisez une photo JPEG, PNG ou WebP de moins de 2 Mo.');
                }
                $bytes = base64_decode($match[2], true);
                $info = $bytes === false ? false : @getimagesizefromstring($bytes);
                if (!$info || !in_array($info['mime'], ['image/jpeg', 'image/png', 'image/webp'], true)
                    || strlen($bytes) > 2 * 1024 * 1024 || $info[0] * $info[1] > 24000000) {
                    $this->invalid('Photo invalide ou trop volumineuse.');
                }
                $total += strlen($bytes);
                if ($total > 12 * 1024 * 1024) {
                    $this->invalid('Les nouvelles photos ne doivent pas dépasser 12 Mo au total.');
                }
                $extension = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'][$info['mime']];
                $photo = 'edl/photos/'.Str::uuid().'.'.$extension;
                $pending[$photo] = $bytes;
            }
            unset($photo);
            $piece['photos'] = $photos;
        }
        unset($piece);
        $uploaded = [];
        try {
            foreach ($pending as $path => $bytes) {
                $uploaded[$path] = upload('public:edl/photos', pathinfo($path, PATHINFO_EXTENSION), $bytes, 'photo');
            }
        } catch (\Throwable $error) {
            Storage::disk('public')->delete(array_values($uploaded));
            throw $error;
        }
        foreach ($pieces as &$piece) {
            $piece['photos'] = array_map(fn ($path) => $uploaded[$path] ?? $path, $piece['photos']);
        }
        unset($piece);
        foreach (array_slice($pieces, $pieceCount) as $elementPhoto) {
            $pieces[$elementPhoto['piece']]['elements'][$elementPhoto['element']]['photo'] = $elementPhoto['photos'][0] ?? null;
        }
        return array_slice($pieces, 0, $pieceCount);
    }

    private function invalid(string $message): never
    {
        throw ValidationException::withMessages(['pieces_json' => $message]);
    }
}
