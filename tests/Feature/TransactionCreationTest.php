<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Currency;
use App\Models\PaymentMethod;
use App\Models\TransactionCategory;
use App\Support\ExpenseBuddyTestHarness;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TransactionCreationTest extends TestCase
{
    #[Test]
    public function admin_can_create_account_category_and_income_transaction(): void
    {
        $admin = ExpenseBuddyTestHarness::install();

        $currency = Currency::query()->where('is_default', true)->firstOrFail();

        $this->actingAs($admin)->post('/accounts', [
            'account_title' => 'PHPUnit Wallet',
            'account_number' => 'PW-100',
            'currency_id' => $currency->id,
            'initial_balance' => '250.0000',
            'note' => 'Created in feature test',
        ])->assertRedirect(route('accounts.index'));

        $account = Account::query()->where('account_number', 'PW-100')->firstOrFail();

        $this->actingAs($admin)->post('/categories', [
            'name' => 'Consulting Income',
            'type' => 'income',
        ])->assertRedirect(route('categories.index'));

        $category = TransactionCategory::query()->where('name', 'Consulting Income')->firstOrFail();

        PaymentMethod::query()->firstOrCreate(['name' => 'Bank Transfer'], ['status' => 'active']);
        $paymentMethod = PaymentMethod::query()->where('name', 'Bank Transfer')->firstOrFail();

        $this->actingAs($admin)->post('/transactions', [
            'account_id' => $account->id,
            'type' => 'income',
            'category_id' => $category->id,
            'payment_method_id' => $paymentMethod->id,
            'currency_id' => $currency->id,
            'amount' => '75.5000',
            'rate_at_transaction' => '1.0000',
            'transaction_date' => now()->toDateString(),
            'reference' => 'PW-TXN-1',
            'description' => 'Feature test income',
        ])->assertRedirect(route('transactions.index'));

        $this->assertDatabaseHas('transactions', [
            'account_id' => $account->id,
            'type' => 'income',
            'reference' => 'PW-TXN-1',
        ]);

        $account->refresh();
        $this->assertSame('325.5000', $account->current_balance);
    }
}
