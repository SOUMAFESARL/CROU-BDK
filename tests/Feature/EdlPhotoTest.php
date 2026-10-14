<?php

namespace Tests\Feature;

use App\Http\Services\EdlPhotoService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class EdlPhotoTest extends TestCase
{
    private function photo(): string
    {
        return 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+jRZkAAAAASUVORK5CYII=';
    }

    public function test_photos_are_saved_and_existing_photos_are_preserved(): void
    {
        Storage::fake('public');
        $service = new EdlPhotoService();
        $pieces = $service->enregistrer([['name_piece' => 'Chambre', 'photos' => [$this->photo()]]]);
        $path = $pieces[0]['photos'][0];
        Storage::disk('public')->assertExists($path);
        $this->assertStringStartsWith('edl/photos/', $path);
        $this->assertSame($pieces, $service->enregistrer($pieces, [$path]));
        $this->assertCount(1, Storage::disk('public')->allFiles());
    }

    public function test_arbitrary_paths_and_invalid_images_are_rejected_before_writing(): void
    {
        Storage::fake('public');
        foreach (['signatures/other.png', 'https://example.org/photo.jpg', 'data:image/png;base64,'.base64_encode('not an image')] as $invalid) {
            try {
                (new EdlPhotoService())->enregistrer([['photos' => [$this->photo(), $invalid]]]);
                $this->fail('Photo invalide acceptée');
            } catch (ValidationException $e) {
                $this->assertArrayHasKey('pieces_json', $e->errors());
                $this->assertSame([], Storage::disk('public')->allFiles());
            }
        }
    }

    public function test_element_photos_are_optional_and_share_validation_with_room_photos(): void
    {
        Storage::fake('public');
        $service = new EdlPhotoService();
        $pieces = $service->enregistrer([['elements' => [
            ['name_element' => 'Sol', 'photo' => $this->photo()],
            ['name_element' => 'Porte'],
        ]]]);
        $path = $pieces[0]['elements'][0]['photo'];
        Storage::disk('public')->assertExists($path);
        $this->assertNull($pieces[0]['elements'][1]['photo']);
        $this->assertSame($pieces, $service->enregistrer($pieces, [$path]));
        $this->assertCount(1, Storage::disk('public')->allFiles());
    }

    public function test_invalid_element_photo_prevents_all_photo_uploads(): void
    {
        Storage::fake('public');
        try {
            (new EdlPhotoService())->enregistrer([[
                'photos' => [$this->photo()],
                'elements' => [['photo' => 'signatures/other.png']],
            ]]);
            $this->fail('Photo étrangère acceptée');
        } catch (ValidationException $error) {
            $this->assertArrayHasKey('pieces_json', $error->errors());
            $this->assertSame([], Storage::disk('public')->allFiles());
        }
    }

    public function test_photo_count_is_limited(): void
    {
        Storage::fake('public');
        $this->expectException(ValidationException::class);
        (new EdlPhotoService())->enregistrer([['photos' => array_fill(0, 7, $this->photo())]]);
    }
}
