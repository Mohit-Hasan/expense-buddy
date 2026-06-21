<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\Schema;

final class AppInstall
{
    public static function lockFile(): string
    {
        return storage_path('installed');
    }

    public static function isInstalled(): bool
    {
        if (is_file(self::lockFile())) {
            return true;
        }

        try {
            if (! Schema::hasTable('users')) {
                return false;
            }

            return User::query()->exists();
        } catch (\Throwable) {
            return false;
        }
    }

    public static function markInstalled(): void
    {
        file_put_contents(self::lockFile(), 'installed_at='.now()->toIso8601String());
    }

    public static function clearLock(): void
    {
        if (is_file(self::lockFile())) {
            unlink(self::lockFile());
        }
    }
}
