<?php

declare(strict_types=1);

namespace App\Support;

use Carbon\Carbon;

final class BalanceTrendPeriod
{
    public const LIFETIME = 'lifetime';

    public const DAYS_7 = '7d';

    public const DAYS_30 = '30d';

    public const DAYS_90 = '90d';

    public const YEAR_1 = '1y';

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return [
            self::LIFETIME => 'Lifetime',
            self::DAYS_7 => '7 days',
            self::DAYS_30 => '30 days',
            self::DAYS_90 => '90 days',
            self::YEAR_1 => '1 year',
        ];
    }

    public static function resolve(?string $period): string
    {
        $period = $period ?? self::LIFETIME;

        return array_key_exists($period, self::options()) ? $period : self::LIFETIME;
    }

    public static function label(string $period): string
    {
        return self::options()[$period] ?? self::options()[self::LIFETIME];
    }

    public static function startDate(string $period): ?Carbon
    {
        return match ($period) {
            self::DAYS_7 => now()->subDays(7)->startOfDay(),
            self::DAYS_30 => now()->subDays(30)->startOfDay(),
            self::DAYS_90 => now()->subDays(90)->startOfDay(),
            self::YEAR_1 => now()->subYear()->startOfDay(),
            default => null,
        };
    }
}
