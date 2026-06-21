<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Services\InstallService;
use App\Support\AppInstall;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;

/**
 * Creates a local admin account via the same path as the web installer (no logo → default SVG icon).
 *
 * Usage:
 *   SEED_DEV_ADMIN=true php artisan db:seed
 *
 * Default login: admin@expensebuddy.test / password
 */
class DevInstallSeeder extends Seeder
{
    public function run(): void
    {
        AppInstall::clearLock();
        Artisan::call('migrate:fresh', ['--force' => true]);

        app(InstallService::class)->install([
            'admin_name' => env('SEED_ADMIN_NAME', 'Administrator'),
            'admin_email' => env('SEED_ADMIN_EMAIL', 'admin@expensebuddy.test'),
            'admin_password' => env('SEED_ADMIN_PASSWORD', 'password'),
            'system_name' => env('SEED_APP_NAME', 'ExpenseBuddy'),
            'currency_name' => 'US Dollar',
            'currency_code' => 'USD',
            'currency_symbol' => '$',
            'allow_negative_balances' => false,
        ], null, true);

        $email = env('SEED_ADMIN_EMAIL', 'admin@expensebuddy.test');

        $this->command?->info('Dev install complete (no custom logo — built-in wallet icon is used).');
        $this->command?->line("Login: {$email} / ".env('SEED_ADMIN_PASSWORD', 'password'));
    }
}
