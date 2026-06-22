<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Account;
use App\Models\Contact;
use App\Models\Currency;
use App\Models\PaymentMethod;
use App\Models\Transaction;
use App\Models\TransactionCategory;
use App\Services\BalanceTrendChartBuilder;
use App\Support\BalanceTrendPeriod;
use App\Support\ExpenseBuddyTestHarness;
use Carbon\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class BalanceTrendChartBuilderTest extends TestCase
{
    private BalanceTrendChartBuilder $builder;

    protected function setUp(): void
    {
        parent::setUp();

        ExpenseBuddyTestHarness::install(withDemo: false);
        $this->builder = new BalanceTrendChartBuilder();
    }

    #[Test]
    public function it_downsamples_many_transactions_to_a_bounded_number_of_points(): void
    {
        Carbon::setTestNow('2026-06-22');

        $contact = $this->createContact();
        $fixtures = $this->ledgerFixtures();

        for ($day = 0; $day < 200; $day++) {
            Transaction::query()->create([
                'account_id' => $fixtures['account']->id,
                'category_id' => $fixtures['category']->id,
                'payment_method_id' => $fixtures['paymentMethod']->id,
                'currency_id' => $fixtures['currency']->id,
                'contact_id' => $contact->id,
                'type' => 'lending_out',
                'amount' => '10.0000',
                'rate_at_transaction' => '1.0000',
                'transaction_date' => now()->subDays(200 - $day)->toDateString(),
            ]);
        }

        $chart = $this->builder->build(
            Transaction::query()->where('contact_id', $contact->id),
            BalanceTrendPeriod::LIFETIME,
        );

        $this->assertSame(200, $chart['meta']['transaction_count']);
        $this->assertLessThanOrEqual(BalanceTrendChartBuilder::MAX_POINTS, $chart['meta']['point_count']);
        $this->assertCount($chart['meta']['point_count'], $chart['labels']);
        $this->assertCount($chart['meta']['point_count'], $chart['values']);
    }

    #[Test]
    public function it_filters_to_a_selected_period_with_opening_balance(): void
    {
        Carbon::setTestNow('2026-06-22');

        $contact = $this->createContact();
        $fixtures = $this->ledgerFixtures();

        Transaction::query()->create([
            'account_id' => $fixtures['account']->id,
            'category_id' => $fixtures['category']->id,
            'payment_method_id' => $fixtures['paymentMethod']->id,
            'currency_id' => $fixtures['currency']->id,
            'contact_id' => $contact->id,
            'type' => 'lending_out',
            'amount' => '100.0000',
            'rate_at_transaction' => '1.0000',
            'transaction_date' => now()->subDays(40)->toDateString(),
        ]);

        Transaction::query()->create([
            'account_id' => $fixtures['account']->id,
            'category_id' => $fixtures['category']->id,
            'payment_method_id' => $fixtures['paymentMethod']->id,
            'currency_id' => $fixtures['currency']->id,
            'contact_id' => $contact->id,
            'type' => 'lending_out',
            'amount' => '25.0000',
            'rate_at_transaction' => '1.0000',
            'transaction_date' => now()->subDays(5)->toDateString(),
        ]);

        $chart = $this->builder->build(
            Transaction::query()->where('contact_id', $contact->id),
            BalanceTrendPeriod::DAYS_30,
        );

        $this->assertSame(125.0, end($chart['values']));
        $this->assertSame(100.0, $chart['values'][0]);
    }

    private function createContact(): Contact
    {
        return Contact::query()->create([
            'name' => 'Trend Contact',
            'type' => 'person',
            'status' => 'active',
            'current_balance' => '0.0000',
        ]);
    }

    /**
     * @return array{account: Account, category: TransactionCategory, paymentMethod: PaymentMethod, currency: Currency}
     */
    private function ledgerFixtures(): array
    {
        $currency = Currency::query()->firstOrFail();
        $paymentMethod = PaymentMethod::query()->firstOrFail();
        $category = TransactionCategory::query()->firstOrFail();

        $account = Account::query()->create([
            'account_number' => 'TREND-001',
            'account_title' => 'Trend Account',
            'currency_id' => $currency->id,
            'initial_balance' => '0.0000',
            'current_balance' => '0.0000',
            'status' => 'active',
        ]);

        return [
            'account' => $account,
            'category' => $category,
            'paymentMethod' => $paymentMethod,
            'currency' => $currency,
        ];
    }
}
