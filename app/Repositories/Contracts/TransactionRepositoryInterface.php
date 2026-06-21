<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\Transaction;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface TransactionRepositoryInterface
{
    public function find(int $id): ?Transaction;

    public function findWithLock(int $id): ?Transaction;

    public function findWithRelations(int $id): ?Transaction;

    /**
     * @param array<string, mixed> $attributes
     */
    public function create(array $attributes): Transaction;

    /**
     * @param array<string, mixed> $attributes
     */
    public function update(int $id, array $attributes): bool;

    public function delete(int $id): bool;

    /**
     * @param array<string, mixed> $filters
     */
    public function paginateFiltered(array $filters, int $perPage = 15): LengthAwarePaginator;

    public function linkTransferPair(int $sourceTransactionId, int $destinationTransactionId): void;
}
