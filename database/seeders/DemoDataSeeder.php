<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Account;
use App\Models\Contact;
use App\Models\Currency;
use App\Models\PaymentMethod;
use App\Models\SystemSetting;
use App\Models\TransactionCategory;
use Illuminate\Database\Seeder;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $settings = SystemSetting::query()->with('defaultCurrency')->first();
        $baseCurrency = $settings?->defaultCurrency ?? Currency::query()->where('is_default', true)->first();

        if ($baseCurrency === null) {
            $this->command?->warn('Demo data skipped — no base currency found.');

            return;
        }

        $bdt = Currency::query()->firstOrCreate(
            ['code' => 'BDT'],
            [
                'name' => 'Bangladeshi Taka',
                'symbol' => '৳',
                'exchange_rate' => '110.0000',
                'is_default' => false,
            ],
        );

        foreach (['Sales Revenue', 'Service Income', 'Interest Income'] as $name) {
            TransactionCategory::query()->firstOrCreate(
                ['name' => $name, 'type' => 'income'],
                ['status' => 'active'],
            );
        }

        foreach (['Office Supplies', 'Utilities', 'Payroll', 'Marketing'] as $name) {
            TransactionCategory::query()->firstOrCreate(
                ['name' => $name, 'type' => 'expense'],
                ['status' => 'active'],
            );
        }

        foreach (['Cash', 'Bank Transfer', 'Credit Card', 'Mobile Banking'] as $method) {
            PaymentMethod::query()->firstOrCreate(
                ['name' => $method],
                ['status' => 'active'],
            );
        }

        Account::query()->firstOrCreate(
            ['account_number' => 'CASH-001'],
            [
                'account_title' => 'Main Cash Account',
                'currency_id' => $baseCurrency->id,
                'initial_balance' => '5000.0000',
                'current_balance' => '5000.0000',
                'note' => 'Primary petty cash and daily operations',
            ],
        );

        Account::query()->firstOrCreate(
            ['account_number' => 'BNK-10042'],
            [
                'account_title' => 'Business Checking',
                'currency_id' => $baseCurrency->id,
                'initial_balance' => '25000.0000',
                'current_balance' => '25000.0000',
                'note' => 'Main business bank account',
            ],
        );

        Account::query()->firstOrCreate(
            ['account_number' => 'BDT-7781'],
            [
                'account_title' => 'BDT Savings',
                'currency_id' => $bdt->id,
                'initial_balance' => '550000.0000',
                'current_balance' => '550000.0000',
                'note' => 'Local currency operations account',
            ],
        );

        Contact::query()->firstOrCreate(
            ['email' => 'jane@example.com'],
            [
                'type' => 'person',
                'name' => 'Jane Doe',
                'phone' => '+1-555-0100',
                'current_balance' => '0.0000',
                'status' => 'active',
            ],
        );

        Contact::query()->firstOrCreate(
            ['name' => 'Office Depot'],
            [
                'type' => 'company',
                'email' => 'accounts@officedepot.example',
                'phone' => '+1-555-0200',
                'company' => 'Office Depot',
                'current_balance' => '0.0000',
                'status' => 'active',
            ],
        );

        $this->command?->info('Demo sample data loaded (accounts, categories, payment methods, contacts).');
    }
}
