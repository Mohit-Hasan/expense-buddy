<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\SystemSetting;
use Illuminate\Support\Facades\Storage;

final class Brand
{
    /** Minimum bytes for an uploaded logo (filters out empty/test placeholders). */
    private const MIN_LOGO_BYTES = 512;

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

    public static function defaultLogoUrl(): string
    {
        return asset('brand-default.svg');
    }

    public static function faviconUrl(): string
    {
        return asset('favicon.svg');
    }

    public static function hasLogo(?SystemSetting $settings = null): bool
    {
        return self::customLogoUrl($settings) !== null;
    }

    public static function customLogoUrl(?SystemSetting $settings = null): ?string
    {
        $path = $settings?->system_logo;

        if ($path === null || $path === '') {
            return null;
        }

        if (! Storage::disk('public')->exists($path)) {
            return null;
        }

        $publicPath = public_path('storage/'.$path);

        if (! self::isUsableLogoFile($publicPath)) {
            return null;
        }

        return asset('storage/'.$path);
    }

    public static function displayLogoUrl(?SystemSetting $settings = null): string
    {
        return self::customLogoUrl($settings) ?? self::defaultLogoUrl();
    }

    public static function displayFaviconUrl(?SystemSetting $settings = null): string
    {
        return self::customLogoUrl($settings) ?? self::faviconUrl();
    }

    /** @deprecated Use customLogoUrl() or displayLogoUrl() */
    public static function logoUrl(?SystemSetting $settings = null): ?string
    {
        return self::customLogoUrl($settings);
    }

    public static function isUsableLogoFile(string $publicPath): bool
    {
        if (! is_file($publicPath)) {
            return false;
        }

        $size = filesize($publicPath);

        if ($size === false || $size < self::MIN_LOGO_BYTES) {
            return false;
        }

        $info = @getimagesize($publicPath);

        if ($info === false || ! isset($info[0], $info[1], $info['mime'])) {
            return false;
        }

        if ($info[0] < 32 || $info[1] < 32) {
            return false;
        }

        return in_array($info['mime'], ['image/jpeg', 'image/png', 'image/gif', 'image/webp'], true);
    }
}
