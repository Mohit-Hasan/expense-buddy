<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        if (filter_var(env('SEED_DEV_ADMIN', false), FILTER_VALIDATE_BOOL)) {
            $this->call(DevInstallSeeder::class);

            if (filter_var(env('SEED_DEMO_DATA', false), FILTER_VALIDATE_BOOL)) {
                $this->call(DemoDataSeeder::class);
            }

            return;
        }

        if (filter_var(env('SEED_DEMO_DATA', false), FILTER_VALIDATE_BOOL)) {
            $this->command?->warn('SEED_DEMO_DATA only loads sample accounts/categories. Run the web installer at /install/ for a full setup.');
            $this->call(DemoDataSeeder::class);

            return;
        }

        $this->command?->info('No data seeded. Open /install/ in your browser to set up ExpenseBuddy.');
        $this->command?->line('For local dev without the installer: SEED_DEV_ADMIN=true php artisan db:seed');
    }
}
