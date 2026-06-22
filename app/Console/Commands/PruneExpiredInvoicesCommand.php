<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\TransactionInvoice;
use Illuminate\Console\Command;

class PruneExpiredInvoicesCommand extends Command
{
    protected $signature = 'expensebuddy:prune-expired-invoices';

    protected $description = 'Disable public access for expired invoice links';

    public function handle(): int
    {
        $count = TransactionInvoice::query()
            ->where('is_public', true)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', now())
            ->update(['is_public' => false]);

        $this->info("Pruned {$count} expired invoice link(s).");

        return self::SUCCESS;
    }
}
