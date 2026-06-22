<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Models\Transaction;
use App\Repositories\Contracts\TransactionRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class TransactionRepository implements TransactionRepositoryInterface
{
    public function find(int $id): ?Transaction
    {
        return Transaction::query()->find($id);
    }

    public function findWithLock(int $id): ?Transaction
    {
        return Transaction::query()
            ->whereKey($id)
            ->lockForUpdate()
            ->first();
    }

    public function findWithRelations(int $id): ?Transaction
    {
        return Transaction::query()
            ->with([
                'account.currency',
                'category',
                'paymentMethod',
                'currency',
                'contact',
                'transferReference',
                'linkedTransfers',
            ])
            ->find($id);
    }

    /**
     * @param array<string, mixed> $attributes
     */
    public function create(array $attributes): Transaction
    {
        return Transaction::query()->create($attributes);
    }

    /**
     * @param array<string, mixed> $attributes
     */
    public function update(int $id, array $attributes): bool
    {
        $transaction = $this->find($id);

        if ($transaction === null) {
            return false;
        }

        return $transaction->update($attributes);
    }

    public function delete(int $id): bool
    {
        $transaction = $this->find($id);

        if ($transaction === null) {
            return false;
        }

        return (bool) $transaction->delete();
    }

    /**
     * @param array<string, mixed> $filters
     */
    public function paginateFiltered(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        $query = Transaction::query()
            ->with([
                'account',
                'category',
                'paymentMethod',
                'currency',
                'contact',
                'latestInvoice',
                'transferReference.account',
            ])
            ->where(function (Builder $builder): void {
                $builder->where('type', '!=', 'transfer')
                    ->orWhereColumn('id', '<', 'transfer_reference_id');
            })
            ->orderByDesc('transaction_date')
            ->orderByDesc('id');

        $this->applyFilters($query, $filters);

        return $query->paginate($perPage);
    }

    public function linkTransferPair(int $sourceTransactionId, int $destinationTransactionId): void
    {
        Transaction::query()
            ->whereKey($sourceTransactionId)
            ->update(['transfer_reference_id' => $destinationTransactionId]);

        Transaction::query()
            ->whereKey($destinationTransactionId)
            ->update(['transfer_reference_id' => $sourceTransactionId]);
    }

    /**
     * @param Builder<Transaction> $query
     * @param array<string, mixed> $filters
     */
    private function applyFilters(Builder $query, array $filters): void
    {
        if (! empty($filters['type'])) {
            $query->where('type', (string) $filters['type']);
        }

        if (! empty($filters['category_id'])) {
            $query->where('category_id', (int) $filters['category_id']);
        }

        if (! empty($filters['account_id'])) {
            $query->where('account_id', (int) $filters['account_id']);
        }

        if (! empty($filters['contact_id'])) {
            $query->where('contact_id', (int) $filters['contact_id']);
        }

        if (! empty($filters['date_from'])) {
            $query->whereDate('transaction_date', '>=', (string) $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->whereDate('transaction_date', '<=', (string) $filters['date_to']);
        }
    }
}
