<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Currency;
use App\Models\SystemSetting;

final class MoneyFormatter
{
    public static function format(string $amount, ?Currency $currency = null, int $decimals = 2): string
    {
        $normalized = bcadd($amount, '0', 4);
        $formatted = number_format((float) $normalized, $decimals);

        if ($currency === null) {
            return $formatted;
        }

        return $currency->symbol.' '.$formatted.' '.$currency->code;
    }

    public static function compact(string $amount, ?Currency $currency = null): string
    {
        $normalized = bcadd($amount, '0', 2);
        $value = (float) $normalized;

        if ($currency === null) {
            if ($value >= 1000000) {
                return number_format($value / 1000000, 1).'M';
            }

            if ($value >= 1000) {
                return number_format($value / 1000, 1).'K';
            }

            return number_format($value, 2);
        }

        $prefix = $currency->symbol.' ';

        if ($value >= 1000000) {
            return $prefix.number_format($value / 1000000, 1).'M';
        }

        if ($value >= 1000) {
            return $prefix.number_format($value / 1000, 1).'K';
        }

        return self::format($amount, $currency, 2);
    }

    public static function convertToBase(string $amount, string $exchangeRate): string
    {
        if (bccomp($exchangeRate, '0', 4) <= 0) {
            return bcadd($amount, '0', 4);
        }

        return bcdiv($amount, $exchangeRate, 4);
    }

    public static function baseCurrency(): ?Currency
    {
        $settings = SystemSetting::query()->with('defaultCurrency')->first();

        if ($settings?->defaultCurrency !== null) {
            return $settings->defaultCurrency;
        }

        return Currency::query()->where('is_default', true)->first()
            ?? Currency::query()->orderBy('id')->first();
    }
}
