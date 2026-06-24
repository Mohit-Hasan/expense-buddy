<?php

declare(strict_types=1);

namespace App\Support;

final class TransactionType
{
    public const LENDING_OUT = 'lending_out';

    public const LENDING_IN = 'lending_in';

    public const LENDING_REPAY_IN = 'lending_repay_in';

    public const LENDING_REPAY_OUT = 'lending_repay_out';

    /**
     * @return list<string>
     */
    public static function creatable(): array
    {
        return ['income', 'expense', self::LENDING_OUT, self::LENDING_IN, self::LENDING_REPAY_IN, self::LENDING_REPAY_OUT];
    }

    /**
     * @return list<string>
     */
    public static function lending(): array
    {
        return [
            self::LENDING_OUT,
            self::LENDING_IN,
            self::LENDING_REPAY_IN,
            self::LENDING_REPAY_OUT,
        ];
    }

    public static function isLending(string $type): bool
    {
        return in_array($type, self::lending(), true);
    }

    public static function label(string $type): string
    {
        return match ($type) {
            self::LENDING_OUT => 'Loan Out',
            self::LENDING_IN => 'Loan In',
            self::LENDING_REPAY_IN => 'Repayment In',
            self::LENDING_REPAY_OUT => 'Repayment Out',
            'lending' => 'Lending',
            default => ucfirst(str_replace('_', ' ', $type)),
        };
    }

    public static function contactBalanceDelta(string $running, string $type, string $amount): string
    {
        return match ($type) {
            'lending', self::LENDING_OUT, self::LENDING_REPAY_OUT => bcadd($running, $amount, 4),
            self::LENDING_IN, self::LENDING_REPAY_IN => bcsub($running, $amount, 4),
            default => $running,
        };
    }
}
