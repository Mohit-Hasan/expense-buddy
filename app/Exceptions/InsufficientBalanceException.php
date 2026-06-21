<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

class InsufficientBalanceException extends RuntimeException
{
    public static function forAccount(int $accountId, string $currentBalance, string $requestedAmount): self
    {
        return new self(
            "Account [{$accountId}] has insufficient balance. Available: {$currentBalance}, requested: {$requestedAmount}."
        );
    }
}
