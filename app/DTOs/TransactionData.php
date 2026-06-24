<?php

declare(strict_types=1);

namespace App\DTOs;

use App\Support\TransactionType;

final readonly class TransactionData
{
    /**
     * @param array<string, mixed> $attributes
     */
    public function __construct(
        public int $accountId,
        public string $type,
        public int $paymentMethodId,
        public int $currencyId,
        public string $amount,
        public string $rateAtTransaction,
        public string $transactionDate,
        public ?int $categoryId = null,
        public ?int $contactId = null,
        public ?string $reference = null,
        public ?string $description = null,
        public ?string $attachment = null,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $type = (string) $data['type'];

        return new self(
            accountId: (int) $data['account_id'],
            type: $type,
            paymentMethodId: (int) $data['payment_method_id'],
            currencyId: (int) $data['currency_id'],
            amount: self::normalizeAmount((string) $data['amount']),
            rateAtTransaction: self::normalizeAmount((string) $data['rate_at_transaction']),
            transactionDate: (string) $data['transaction_date'],
            categoryId: isset($data['category_id']) ? (int) $data['category_id'] : null,
            contactId: ! empty($data['contact_id']) ? (int) $data['contact_id'] : null,
            reference: isset($data['reference']) ? (string) $data['reference'] : null,
            description: isset($data['description']) ? (string) $data['description'] : null,
            attachment: isset($data['attachment']) ? (string) $data['attachment'] : null,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toAttributes(): array
    {
        return [
            'account_id' => $this->accountId,
            'type' => $this->type,
            'category_id' => $this->categoryId,
            'payment_method_id' => $this->paymentMethodId,
            'currency_id' => $this->currencyId,
            'amount' => $this->amount,
            'rate_at_transaction' => $this->rateAtTransaction,
            'contact_id' => $this->contactId,
            'transaction_date' => $this->transactionDate,
            'reference' => $this->reference,
            'description' => $this->description,
            'attachment' => $this->attachment,
        ];
    }

    private static function normalizeAmount(string $amount): string
    {
        return bcadd($amount, '0', 4);
    }
}
