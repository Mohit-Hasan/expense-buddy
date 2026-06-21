<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\Contact;
use Illuminate\Database\Eloquent\Collection;

interface ContactRepositoryInterface
{
    /**
     * @return Collection<int, Contact>
     */
    public function allActive(): Collection;

    public function find(int $id): ?Contact;

    public function findWithLock(int $id): ?Contact;

    /**
     * @return Collection<int, Contact>
     */
    public function findByType(string $type): Collection;

    /**
     * @param array<string, mixed> $attributes
     */
    public function create(array $attributes): Contact;

    /**
     * @param array<string, mixed> $attributes
     */
    public function update(int $id, array $attributes): bool;

    public function updateBalance(int $id, string $amount, string $operationType): bool;
}
