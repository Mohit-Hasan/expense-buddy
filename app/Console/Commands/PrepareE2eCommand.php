<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Support\ExpenseBuddyTestHarness;

class PrepareE2eCommand extends Command
{
    protected $signature = 'expensebuddy:prepare-e2e
                            {--uninstalled : Leave a fresh schema without admin user for installer UI tests}
                            {--demo : Include demo accounts and categories after install}';

    protected $description = 'Prepare the database for Playwright end-to-end tests';

    public function handle(): int
    {
        if ($this->option('uninstalled')) {
            ExpenseBuddyTestHarness::prepareUninstalled();
            $this->info('Database schema is fresh and ready for /install/ UI tests.');

            return self::SUCCESS;
        }

        ExpenseBuddyTestHarness::install((bool) $this->option('demo'));

        $this->info('E2E database ready.');
        $this->line('Login: '.ExpenseBuddyTestHarness::ADMIN_EMAIL.' / '.ExpenseBuddyTestHarness::ADMIN_PASSWORD);

        return self::SUCCESS;
    }
}
