<?php

namespace App\Repositories\Interfaces;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface UserRepositoryInterface
{
    public function getAll(): Collection;

    public function paginate(?string $search = null, ?string $statut = null, int $perPage = 10): LengthAwarePaginator;

    public function findById(string $id): User;

    public function create(array $data): User;

    public function update(string $id, array $data): User;

    public function delete(string $id): bool;

    public function existsByEmail(string $email, ?string $excludeId = null): bool;
}