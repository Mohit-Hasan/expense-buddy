<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Currency;
use App\Models\Transaction;
use App\Services\DashboardService;
use App\Support\ExpenseBuddyTestHarness;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DashboardCurrencyConversionTest extends TestCase
{
    #[Test]
    public function dashboard_totals_convert_foreign_income_to_base_currency(): void
    {
        ExpenseBuddyTestHarness::install(withDemo: true);

        $base = Currency::query()->where('is_default', true)->firstOrFail();
        $usd = Currency::query()->where('code', 'USD')->firstOrFail();
        $usd->update(['exchange_rate' => '0.0081']);

        $account = \App\Models\Account::query()->firstOrFail();

        Transaction::query()->create([
            'account_id' => $account->id,
            'type' => 'income',
            'category_id' => null,
            'payment_method_id' => \App\Models\PaymentMethod::query()->firstOrFail()->id,
            'currency_id' => $usd->id,
            'amount' => '140.0000',
            'rate_at_transaction' => '0.0081',
            'contact_id' => null,
            'transaction_date' => now()->toDateString(),
        ]);

        $summary = app(DashboardService::class)->getSummary();

        $this->assertSame('17283.9506', $summary['total_income']);
        $this->assertSame($base->code, $summary['base_currency']?->code);
    }
}
