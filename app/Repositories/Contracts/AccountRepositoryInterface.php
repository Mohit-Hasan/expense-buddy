<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\Account;
use Illuminate\Database\Eloquent\Collection;

interface AccountRepositoryInterface
{
    /**
     * @return Collection<int, Account>
     */
    public function all(): Collection;

    public function find(int $id): ?Account;

    public function findWithLock(int $id): ?Account;

    public function findWithCurrency(int $id): ?Account;

    /**
     * @param array<string, mixed> $attributes
     */
    public function create(array $attributes): Account;

    /**
     * @param array<string, mixed> $attributes
     */
    public function update(int $id, array $attributes): bool;

    public function updateBalance(int $id, string $amount, string $operationType): bool;
}
