<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\SystemSetting;

final class Brand
{
    public static function name(): string
    {
        return (string) config('brand.name', 'ExpenseBuddy');
    }

    public static function tagline(): string
    {
        return (string) config('brand.tagline', 'Your Personal Finance Companion');
    }

    public static function fullName(): string
    {
        return self::name().' — '.self::tagline();
    }

    public static function appName(?SystemSetting $settings = null): string
    {
        return $settings?->system_name ?? self::name();
    }

    public static function logoUrl(?SystemSetting $settings = null): ?string
    {
        if ($settings?->system_logo) {
            return asset('storage/'.$settings->system_logo);
        }

        return null;
    }
}
