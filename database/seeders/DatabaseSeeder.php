<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        if (filter_var(env('SEED_DEMO_DATA', false), FILTER_VALIDATE_BOOL)) {
            $this->call(DemoSeeder::class);

            return;
        }

        $this->command?->info('No seed data loaded. Visit /install in your browser to set up ExpenseBuddy.');
        $this->command?->info('Optional demo dataset: SEED_DEMO_DATA=true php artisan db:seed');
    }
}
