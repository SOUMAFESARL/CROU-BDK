<?php

namespace App\Http\Services;

use App\Models\User;
use App\Repositories\Interfaces\UserRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class UserService
{
    protected  $userRepository;
    public function __construct(
        UserRepositoryInterface $userRepository
    ) {
        $this->userRepository = $userRepository;
    }

    public function getAllUsers(): Collection
    {
        return $this->userRepository->getAll();
    }

    public function paginateUsers(?string $search = null, ?string $statut = null, int $perPage = 10): LengthAwarePaginator
    {
        return $this->userRepository->paginate($search, $statut, $perPage);
    }

    public function getUserById(string $id): User
    {
        return $this->userRepository->findById($id);
    }

    public function createUser(array $data, ?UploadedFile $photo = null): User
    {
        $payload = $this->buildPayload($data);

        $payload['password']        = Hash::make($data['password']);
        $payload['date_inscription'] = now();
        $payload['statut']           = 'active';
        $payload['campus_id']            = getInfoAdmin()->users->campus_id ?? null;
        $payload['created_by']       = getInfoAdmin()->users->code ?? null;
        $payload['remember_token']   = Str::random(60);

        if ($photo) {
           // $payload['photo'] = $this->storePhoto($photo);
            $payload['photo'] = upload('users',  'png', $data, 'photo');
        }

        return $this->userRepository->create($payload);
    }

    public function updateUser(string $id, array $data, ?UploadedFile $photo = null): User
    {
        $user    = $this->userRepository->findById($id);
        $payload = $this->buildPayload($data);

        // Mise à jour mot de passe si fourni
        if (!empty($data['password'])) {
            $payload['password'] = Hash::make($data['password']);
        }

        $payload['updated_by'] = auth()->id();

        // Gestion photo
        if ($photo) {
            //$this->deleteOldPhoto($user->photo);

          //  $payload['photo'] = $this->storePhoto($photo);
            $payload['photo'] = update( 'users', $user->photo, 'png', $data, 'photo');
        }

        return $this->userRepository->update($id, $payload);
    }

    public function deleteUser(string $id): bool
    {
        $user = $this->userRepository->findById($id);
       // $this->deleteOldPhoto($user->photo);
        delete( 'users', $user->photo);
        return $this->userRepository->delete($id);
    }

    // ─────────────────────────────────────────────
    //  Helpers privés
    // ─────────────────────────────────────────────

    private function buildPayload(array $data): array
    {
        return [
            'name'                 => $data['name'],
            'genre_id'             => $data['genre_id'],
            'telephone_principal'  => $data['telephone_principal'],
            'email'                => $data['email'],
            'role_id'              => $data['role_id'],
        ];
    }

    private function storePhoto(UploadedFile $photo): string
    {
        return $photo->store('users', 'public');
    }

    private function deleteOldPhoto(?string $photoPath): void
    {
        if ($photoPath && Storage::disk('public')->exists($photoPath)) {
            Storage::disk('public')->delete($photoPath);
        }
    }
}