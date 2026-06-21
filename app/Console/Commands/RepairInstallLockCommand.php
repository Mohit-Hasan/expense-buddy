<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Support\AppInstall;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class RepairInstallLockCommand extends Command
{
    protected $signature = 'expensebuddy:repair-install-lock';

    protected $description = 'Create storage/installed lock when the app is already set up (e.g. after db:seed)';

    public function handle(): int
    {
        if (! AppInstall::isInstalled()) {
            $this->error('No administrator user found. Run /install/ or SEED_DEV_ADMIN=true php artisan db:seed first.');

            return self::FAILURE;
        }

        if (is_file(AppInstall::lockFile())) {
            $this->info('Install lock already exists.');

            return self::SUCCESS;
        }

        AppInstall::markInstalled();
        $this->info('Install lock created at storage/installed');

        if (! is_link(public_path('storage')) && ! is_dir(public_path('storage'))) {
            Artisan::call('storage:link');
            $this->info('Created public/storage symlink.');
        }

        return self::SUCCESS;
    }
}
