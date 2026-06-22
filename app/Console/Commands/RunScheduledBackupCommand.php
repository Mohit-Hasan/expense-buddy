<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\DatabaseBackupService;
use Illuminate\Console\Command;

class RunScheduledBackupCommand extends Command
{
    protected $signature = 'expensebuddy:run-scheduled-backup';

    protected $description = 'Send scheduled database backup email when due';

    public function handle(DatabaseBackupService $backupService): int
    {
        if ($backupService->runScheduledBackup()) {
            $this->info('Scheduled backup emailed successfully.');

            return self::SUCCESS;
        }

        $this->line('No scheduled backup was due.');

        return self::SUCCESS;
    }
}
