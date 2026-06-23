<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\DatabaseBackupService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class RunScheduledBackupCommand extends Command
{
    protected $signature = 'expensebuddy:run-scheduled-backup';

    protected $description = 'Send scheduled database backup email when due';

    public function handle(DatabaseBackupService $backupService): int
    {
        try {
            $this->line('Database: '.$backupService->connectionLabel());

            if ($backupService->runScheduledBackup()) {
                $this->info('Scheduled backup emailed successfully.');

                return self::SUCCESS;
            }

            $this->line('Scheduler checked; no backup was due.');

            return self::SUCCESS;
        } catch (\Throwable $exception) {
            Log::error('Scheduled backup command failed: '.$exception->getMessage(), [
                'exception' => $exception,
            ]);
            $this->error('Scheduled backup command failed: '.$exception->getMessage());

            return self::FAILURE;
        }
    }
}
