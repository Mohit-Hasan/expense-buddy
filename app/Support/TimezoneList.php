<?php

declare(strict_types=1);

namespace App\Support;

use DateTimeImmutable;
use DateTimeZone;

final class TimezoneList
{
    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        $options = [];
        $now = new DateTimeImmutable('now', new DateTimeZone('UTC'));

        foreach (DateTimeZone::listIdentifiers() as $identifier) {
            $options[$identifier] = self::formatLabel($identifier, $now);
        }

        return $options;
    }

    public static function formatLabel(string $identifier, ?DateTimeImmutable $now = null): string
    {
        $now ??= new DateTimeImmutable('now', new DateTimeZone('UTC'));
        $timezone = new DateTimeZone($identifier);
        $offset = $timezone->getOffset($now);
        $sign = $offset >= 0 ? '+' : '-';
        $absolute = abs($offset);
        $hours = str_pad((string) intdiv($absolute, 3600), 2, '0', STR_PAD_LEFT);
        $minutes = str_pad((string) (intdiv($absolute, 60) % 60), 2, '0', STR_PAD_LEFT);

        return "UTC{$sign}{$hours}:{$minutes} — {$identifier}";
    }
}
