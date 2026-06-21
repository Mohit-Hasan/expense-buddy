<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Models\Account;
use App\Repositories\Contracts\AccountRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use InvalidArgumentException;

class AccountRepository implements AccountRepositoryInterface
{
    /**
     * @return Collection<int, Account>
     */
    public function all(): Collection
    {
        return Account::query()
            ->with(['currency'])
            ->orderBy('account_title')
            ->get();
    }

    public function find(int $id): ?Account
    {
        return Account::query()->find($id);
    }

    public function findWithLock(int $id): ?Account
    {
        return Account::query()
            ->whereKey($id)
            ->lockForUpdate()
            ->first();
    }

    public function findWithCurrency(int $id): ?Account
    {
        return Account::query()
            ->with(['currency'])
            ->find($id);
    }

    /**
     * @param array<string, mixed> $attributes
     */
    public function create(array $attributes): Account
    {
        return Account::query()->create($attributes);
    }

    /**
     * @param array<string, mixed> $attributes
     */
    public function update(int $id, array $attributes): bool
    {
        $account = $this->find($id);

        if ($account === null) {
            return false;
        }

        return $account->update($attributes);
    }

    public function updateBalance(int $id, string $amount, string $operationType): bool
    {
        $account = $this->findWithLock($id);

        if ($account === null) {
            return false;
        }

        $currentBalance = (string) $account->current_balance;

        $newBalance = match ($operationType) {
            'increment' => bcadd($currentBalance, $amount, 4),
            'decrement' => bcsub($currentBalance, $amount, 4),
            default => throw new InvalidArgumentException("Invalid balance operation type: {$operationType}"),
        };

        return $account->update(['current_balance' => $newBalance]);
    }
}
