<?php

declare(strict_types=1);

namespace App\Services;

use App\DTOs\TransactionData;
use App\DTOs\TransferData;
use App\Exceptions\InsufficientBalanceException;
use App\Models\Transaction;
use App\Repositories\Contracts\AccountRepositoryInterface;
use App\Repositories\Contracts\ContactRepositoryInterface;
use App\Repositories\Contracts\TransactionRepositoryInterface;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

class TransactionService
{
    public function __construct(
        private readonly AccountRepositoryInterface $accountRepository,
        private readonly ContactRepositoryInterface $contactRepository,
        private readonly TransactionRepositoryInterface $transactionRepository,
    ) {
    }

    public function createIncome(TransactionData $dto): Transaction
    {
        if ($dto->type !== 'income') {
            throw new InvalidArgumentException('Transaction type must be income.');
        }

        return DB::transaction(function () use ($dto): Transaction {
            $this->assertPositiveAmount($dto->amount);
            $this->assertAccountExists($dto->accountId);

            $transaction = $this->transactionRepository->create($dto->toAttributes());

            $this->accountRepository->updateBalance($dto->accountId, $dto->amount, 'increment');

            if ($dto->contactId !== null) {
                $this->contactRepository->updateBalance($dto->contactId, $dto->amount, 'decrement');
            }

            return $this->transactionRepository->findWithRelations($transaction->id)
                ?? $transaction;
        });
    }

    public function createExpense(TransactionData $dto): Transaction
    {
        if ($dto->type !== 'expense') {
            throw new InvalidArgumentException('Transaction type must be expense.');
        }

        return DB::transaction(function () use ($dto): Transaction {
            $this->assertPositiveAmount($dto->amount);
            $this->assertSufficientBalance($dto->accountId, $dto->amount);

            $transaction = $this->transactionRepository->create($dto->toAttributes());

            $this->accountRepository->updateBalance($dto->accountId, $dto->amount, 'decrement');

            return $this->transactionRepository->findWithRelations($transaction->id)
                ?? $transaction;
        });
    }

    public function createLending(TransactionData $dto): Transaction
    {
        if ($dto->type !== 'lending') {
            throw new InvalidArgumentException('Transaction type must be lending.');
        }

        if ($dto->contactId === null) {
            throw new InvalidArgumentException('Lending transactions require a linked person or company.');
        }

        return DB::transaction(function () use ($dto): Transaction {
            $this->assertPositiveAmount($dto->amount);
            $this->assertSufficientBalance($dto->accountId, $dto->amount);
            $this->assertAccountExists($dto->accountId);

            $attributes = $dto->toAttributes();
            $attributes['category_id'] = null;

            $transaction = $this->transactionRepository->create($attributes);

            $this->accountRepository->updateBalance($dto->accountId, $dto->amount, 'decrement');
            $this->contactRepository->updateBalance($dto->contactId, $dto->amount, 'increment');

            return $this->transactionRepository->findWithRelations($transaction->id)
                ?? $transaction;
        });
    }

    /**
     * @return array{expense: Transaction, income: Transaction}
     */
    public function createTransfer(TransferData $dto): array
    {
        if ($dto->sourceAccountId === $dto->destinationAccountId) {
            throw new InvalidArgumentException('Source and destination accounts must differ.');
        }

        return DB::transaction(function () use ($dto): array {
            $this->assertPositiveAmount($dto->amount);
            $this->assertSufficientBalance($dto->sourceAccountId, $dto->amount);
            $this->assertAccountExists($dto->sourceAccountId);
            $this->assertAccountExists($dto->destinationAccountId);

            $expenseTransaction = $this->transactionRepository->create([
                'account_id' => $dto->sourceAccountId,
                'type' => 'transfer',
                'category_id' => null,
                'payment_method_id' => $dto->paymentMethodId,
                'currency_id' => $dto->currencyId,
                'amount' => $dto->amount,
                'rate_at_transaction' => $dto->rateAtTransaction,
                'contact_id' => null,
                'transaction_date' => $dto->transactionDate,
                'reference' => $dto->reference,
                'description' => $dto->description,
                'attachment' => $dto->attachment,
            ]);

            $incomeTransaction = $this->transactionRepository->create([
                'account_id' => $dto->destinationAccountId,
                'type' => 'transfer',
                'category_id' => null,
                'payment_method_id' => $dto->paymentMethodId,
                'currency_id' => $dto->currencyId,
                'amount' => $dto->amount,
                'rate_at_transaction' => $dto->rateAtTransaction,
                'contact_id' => null,
                'transaction_date' => $dto->transactionDate,
                'reference' => $dto->reference,
                'description' => $dto->description,
                'attachment' => $dto->attachment,
            ]);

            $this->transactionRepository->linkTransferPair(
                $expenseTransaction->id,
                $incomeTransaction->id
            );

            $this->accountRepository->updateBalance($dto->sourceAccountId, $dto->amount, 'decrement');
            $this->accountRepository->updateBalance($dto->destinationAccountId, $dto->amount, 'increment');

            $expense = $this->transactionRepository->findWithRelations($expenseTransaction->id);
            $income = $this->transactionRepository->findWithRelations($incomeTransaction->id);

            if ($expense === null || $income === null) {
                throw new RuntimeException('Failed to load transfer transactions after creation.');
            }

            return [
                'expense' => $expense,
                'income' => $income,
            ];
        });
    }

    public function update(int $id, TransactionData $dto): Transaction
    {
        return DB::transaction(function () use ($id, $dto): Transaction {
            $existing = $this->transactionRepository->findWithLock($id);

            if ($existing === null) {
                throw new InvalidArgumentException("Transaction [{$id}] not found.");
            }

            if ($existing->type === 'transfer') {
                throw new InvalidArgumentException('Transfer transactions must be updated as a paired operation.');
            }

            $this->reverseTransactionEffects($existing);
            $this->assertPositiveAmount($dto->amount);

            if (in_array($dto->type, ['expense', 'lending'], true)) {
                $this->assertSufficientBalance($dto->accountId, $dto->amount);
            }

            if ($dto->type === 'lending' && $dto->contactId === null) {
                throw new InvalidArgumentException('Lending transactions require a linked person or company.');
            }

            $attributes = $dto->toAttributes();

            if ($dto->type === 'lending') {
                $attributes['category_id'] = null;
            }

            $existing->update($attributes);
            $this->applyTransactionEffects($dto);

            $updated = $this->transactionRepository->findWithRelations($id);

            if ($updated === null) {
                throw new RuntimeException("Failed to reload transaction [{$id}] after update.");
            }

            return $updated;
        });
    }

    public function delete(int $id): bool
    {
        return DB::transaction(function () use ($id): bool {
            $transaction = $this->transactionRepository->findWithLock($id);

            if ($transaction === null) {
                return false;
            }

            if ($transaction->type === 'transfer') {
                return $this->deleteTransferPair($transaction);
            }

            $this->reverseTransactionEffects($transaction);

            return $this->transactionRepository->delete($id);
        });
    }

    private function deleteTransferPair(Transaction $transaction): bool
    {
        $pairedId = $transaction->transfer_reference_id;

        if ($pairedId === null) {
            throw new RuntimeException("Transfer transaction [{$transaction->id}] is missing its paired reference.");
        }

        $paired = $this->transactionRepository->findWithLock((int) $pairedId);

        if ($paired === null) {
            throw new RuntimeException("Paired transfer transaction [{$pairedId}] not found.");
        }

        $this->reverseTransactionEffects($transaction);
        $this->reverseTransactionEffects($paired);

        $deletedPrimary = $this->transactionRepository->delete($transaction->id);
        $deletedPaired = $this->transactionRepository->delete($paired->id);

        return $deletedPrimary && $deletedPaired;
    }

    private function reverseTransactionEffects(Transaction $transaction): void
    {
        $amount = (string) $transaction->amount;
        $accountId = (int) $transaction->account_id;

        match ($transaction->type) {
            'income' => $this->reverseIncomeEffects($transaction, $amount, $accountId),
            'expense' => $this->reverseExpenseEffects($transaction, $amount, $accountId),
            'lending' => $this->reverseLendingEffects($transaction, $amount, $accountId),
            'transfer' => $this->reverseTransferEffects($transaction, $amount, $accountId),
            default => throw new InvalidArgumentException("Unknown transaction type: {$transaction->type}"),
        };
    }

    private function reverseIncomeEffects(Transaction $transaction, string $amount, int $accountId): void
    {
        $this->accountRepository->updateBalance($accountId, $amount, 'decrement');

        if ($transaction->contact_id !== null) {
            $this->contactRepository->updateBalance((int) $transaction->contact_id, $amount, 'increment');
        }
    }

    private function reverseExpenseEffects(Transaction $transaction, string $amount, int $accountId): void
    {
        $this->accountRepository->updateBalance($accountId, $amount, 'increment');
    }

    private function reverseLendingEffects(Transaction $transaction, string $amount, int $accountId): void
    {
        $this->accountRepository->updateBalance($accountId, $amount, 'increment');

        if ($transaction->contact_id !== null) {
            $this->contactRepository->updateBalance((int) $transaction->contact_id, $amount, 'decrement');
        }
    }

    private function reverseTransferEffects(Transaction $transaction, string $amount, int $accountId): void
    {
        if ($transaction->transfer_reference_id === null) {
            throw new RuntimeException("Transfer [{$transaction->id}] is missing its paired reference.");
        }

        if ($transaction->id < (int) $transaction->transfer_reference_id) {
            $this->accountRepository->updateBalance($accountId, $amount, 'increment');

            return;
        }

        $this->accountRepository->updateBalance($accountId, $amount, 'decrement');
    }

    private function applyTransactionEffects(TransactionData $dto): void
    {
        match ($dto->type) {
            'income' => $this->applyIncomeEffects($dto),
            'expense' => $this->applyExpenseEffects($dto),
            'lending' => $this->applyLendingEffects($dto),
            default => throw new InvalidArgumentException("Unsupported transaction type: {$dto->type}"),
        };
    }

    private function applyIncomeEffects(TransactionData $dto): void
    {
        $this->accountRepository->updateBalance($dto->accountId, $dto->amount, 'increment');

        if ($dto->contactId !== null) {
            $this->contactRepository->updateBalance($dto->contactId, $dto->amount, 'decrement');
        }
    }

    private function applyExpenseEffects(TransactionData $dto): void
    {
        $this->accountRepository->updateBalance($dto->accountId, $dto->amount, 'decrement');
    }

    private function applyLendingEffects(TransactionData $dto): void
    {
        $this->accountRepository->updateBalance($dto->accountId, $dto->amount, 'decrement');

        if ($dto->contactId !== null) {
            $this->contactRepository->updateBalance($dto->contactId, $dto->amount, 'increment');
        }
    }

    private function assertPositiveAmount(string $amount): void
    {
        if (bccomp($amount, '0', 4) <= 0) {
            throw new InvalidArgumentException('Transaction amount must be greater than zero.');
        }
    }

    private function assertAccountExists(int $accountId): void
    {
        if ($this->accountRepository->find($accountId) === null) {
            throw new InvalidArgumentException("Account [{$accountId}] not found.");
        }
    }

    private function assertSufficientBalance(int $accountId, string $amount): void
    {
        if (config('ledger.allow_negative_balances', false)) {
            return;
        }

        $account = $this->accountRepository->findWithLock($accountId);

        if ($account === null) {
            throw new InvalidArgumentException("Account [{$accountId}] not found.");
        }

        $currentBalance = (string) $account->current_balance;

        if (bccomp($currentBalance, $amount, 4) < 0) {
            throw InsufficientBalanceException::forAccount($accountId, $currentBalance, $amount);
        }
    }
}
