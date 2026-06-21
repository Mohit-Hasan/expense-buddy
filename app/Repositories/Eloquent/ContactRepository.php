<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Models\Contact;
use App\Repositories\Contracts\ContactRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use InvalidArgumentException;

class ContactRepository implements ContactRepositoryInterface
{
    /**
     * @return Collection<int, Contact>
     */
    public function allActive(): Collection
    {
        return Contact::query()
            ->where('status', 'active')
            ->orderBy('name')
            ->get();
    }

    public function find(int $id): ?Contact
    {
        return Contact::query()->find($id);
    }

    public function findWithLock(int $id): ?Contact
    {
        return Contact::query()
            ->whereKey($id)
            ->lockForUpdate()
            ->first();
    }

    /**
     * @return Collection<int, Contact>
     */
    public function findByType(string $type): Collection
    {
        return Contact::query()
            ->where('type', $type)
            ->where('status', 'active')
            ->orderBy('name')
            ->get();
    }

    /**
     * @param array<string, mixed> $attributes
     */
    public function create(array $attributes): Contact
    {
        return Contact::query()->create($attributes);
    }

    /**
     * @param array<string, mixed> $attributes
     */
    public function update(int $id, array $attributes): bool
    {
        $contact = $this->find($id);

        if ($contact === null) {
            return false;
        }

        return $contact->update($attributes);
    }

    public function updateBalance(int $id, string $amount, string $operationType): bool
    {
        $contact = $this->findWithLock($id);

        if ($contact === null) {
            return false;
        }

        $currentBalance = (string) $contact->current_balance;

        $newBalance = match ($operationType) {
            'increment' => bcadd($currentBalance, $amount, 4),
            'decrement' => bcsub($currentBalance, $amount, 4),
            default => throw new InvalidArgumentException("Invalid balance operation type: {$operationType}"),
        };

        return $contact->update(['current_balance' => $newBalance]);
    }
}
