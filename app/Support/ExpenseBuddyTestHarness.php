<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Account;
use App\Models\Contact;
use App\Models\Currency;
use App\Models\PaymentMethod;
use App\Models\Transaction;
use App\Models\TransactionCategory;
use App\Models\TransactionInvoice;
use App\Models\User;
use App\Services\InstallService;
use Database\Seeders\DemoDataSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;

final class ExpenseBuddyTestHarness
{
    public const ADMIN_NAME = 'Test Admin';

    public const ADMIN_EMAIL = 'admin@expensebuddy.test';

    public const ADMIN_PASSWORD = 'password';

    public static function freshSchema(): void
    {
        AppInstall::clearLock();
        Artisan::call('migrate:fresh', ['--force' => true]);
    }

    public static function install(bool $withDemo = false, bool $withLogo = false): User
    {
        self::freshSchema();

        $logo = $withLogo ? UploadedFile::fake()->image('logo.png', 128, 128) : null;

        app(InstallService::class)->install([
            'admin_name' => self::ADMIN_NAME,
            'admin_email' => self::ADMIN_EMAIL,
            'admin_password' => self::ADMIN_PASSWORD,
            'system_name' => 'ExpenseBuddy Test',
            'currency_name' => 'US Dollar',
            'currency_code' => 'USD',
            'currency_symbol' => '$',
            'allow_negative_balances' => false,
        ], $logo, true);

        if ($withDemo) {
            (new DemoDataSeeder())->run();
        } else {
            self::seedMinimalLedgerFixtures();
        }

        return User::query()->where('email', self::ADMIN_EMAIL)->firstOrFail();
    }

    public static function prepareUninstalled(): void
    {
        AppInstall::clearLock();
        Artisan::call('migrate:fresh', ['--force' => true]);
    }

    public static function seedMinimalLedgerFixtures(): void
    {
        PaymentMethod::query()->firstOrCreate(
            ['name' => 'Cash'],
            ['status' => 'active'],
        );

        TransactionCategory::query()->firstOrCreate(
            ['name' => 'Test Income', 'type' => 'income'],
            ['status' => 'active'],
        );

        TransactionCategory::query()->firstOrCreate(
            ['name' => 'Test Expense', 'type' => 'expense'],
            ['status' => 'active'],
        );

        $currency = Currency::query()->where('is_default', true)->firstOrFail();

        Account::query()->firstOrCreate(
            ['account_number' => 'TEST-001'],
            [
                'account_title' => 'Test Cash Account',
                'currency_id' => $currency->id,
                'initial_balance' => '1000.0000',
                'current_balance' => '1000.0000',
            ],
        );

        Contact::query()->firstOrCreate(
            ['email' => 'contact@test.local'],
            [
                'type' => 'person',
                'name' => 'Test Contact',
                'current_balance' => '0.0000',
                'status' => 'active',
            ],
        );
    }

    /**
     * @return array{transaction: Transaction, invoice: TransactionInvoice}
     */
    public static function createSampleTransaction(): array
    {
        $account = Account::query()->firstOrFail();
        $category = TransactionCategory::query()->where('type', 'income')->firstOrFail();
        $paymentMethod = PaymentMethod::query()->firstOrFail();
        $currency = Currency::query()->findOrFail($account->currency_id);

        $transaction = Transaction::query()->create([
            'account_id' => $account->id,
            'type' => 'income',
            'category_id' => $category->id,
            'payment_method_id' => $paymentMethod->id,
            'currency_id' => $currency->id,
            'amount' => '150.0000',
            'rate_at_transaction' => '1.0000',
            'transaction_date' => now()->toDateString(),
            'reference' => 'TEST-REF-001',
            'description' => 'Sample transaction for automated tests',
        ]);

        $invoice = TransactionInvoice::query()->create([
            'transaction_id' => $transaction->id,
            'invoice_number' => 'INV-TEST-001',
            'public_token' => Str::random(48),
            'is_public' => true,
        ]);

        return ['transaction' => $transaction, 'invoice' => $invoice];
    }
}
