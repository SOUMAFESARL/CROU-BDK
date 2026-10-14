<?php

namespace App\Repositories;

use App\Models\User;
use App\Repositories\Interfaces\UserRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class UserRepository implements UserRepositoryInterface
{
    public function getAll(): Collection
    {
        return User::with(['genre', 'region', 'ville', 'type_utilisateur', 'role'])
            ->latest()
            ->get();
    }

    /** Liste paginée côté serveur (recherche sur nom, e-mail, matricule, téléphone et rôle). */
    public function paginate(?string $search = null, ?string $statut = null, int $perPage = 10): LengthAwarePaginator
    {
        $search = trim((string) $search);

        return User::with(['genre', 'role'])
            ->when($search !== '', function ($query) use ($search) {
                $like = '%'.$search.'%';
                $query->where(function ($query) use ($like) {
                    $query->where('name', 'like', $like)
                        ->orWhere('email', 'like', $like)
                        ->orWhere('matricule', 'like', $like)
                        ->orWhere('telephone_principal', 'like', $like)
                        ->orWhereHas('role', fn ($role) => $role->where('name', 'like', $like));
                });
            })
            ->when(in_array($statut, ['active', 'inactive'], true), fn ($query) => $query->where('statut', $statut))
            ->latest()
            ->paginate($perPage)
            ->withQueryString();
    }

    public function findById(string $id): User
    {
        return User::with(['genre', 'region', 'ville', 'type_utilisateur', 'role'])
            ->where('id_user', $id)
            ->firstOrFail();
    }

    public function create(array $data): User
    {
        return User::create($data);
    }

    public function update(string $id, array $data): User
    {
        $user = $this->findById($id);
        $user->update($data);
        return $user->fresh();
    }

    public function delete(string $id): bool
    {
        $user = $this->findById($id);
        return $user->delete();
    }

    public function existsByEmail(string $email, ?string $excludeId = null): bool
    {
        $query = User::where('email', $email);

        if ($excludeId) {
            $query->where('id_user', '!=', $excludeId);
        }

        return $query->exists();
    }
}