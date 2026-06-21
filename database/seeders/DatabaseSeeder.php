<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Account;
use App\Models\Contact;
use App\Models\Currency;
use App\Models\PaymentMethod;
use App\Models\Role;
use App\Models\SystemSetting;
use App\Models\TransactionCategory;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $adminRole = Role::query()->create([
            'name' => 'Administrator',
            'slug' => 'administrator',
        ]);

        $managerRole = Role::query()->create([
            'name' => 'Manager',
            'slug' => 'manager',
        ]);

        $this->call(MenuPermissionSeeder::class);

        User::query()->create([
            'name' => 'System Admin',
            'email' => 'admin@ledger.local',
            'password' => Hash::make('password'),
            'role_id' => $adminRole->id,
            'status' => 'active',
        ]);

        $usd = Currency::query()->create([
            'name' => 'US Dollar',
            'code' => 'USD',
            'symbol' => '$',
            'exchange_rate' => '1.0000',
            'is_default' => true,
        ]);

        $bdt = Currency::query()->create([
            'name' => 'Bangladeshi Taka',
            'code' => 'BDT',
            'symbol' => '৳',
            'exchange_rate' => '110.0000',
            'is_default' => false,
        ]);

        $incomeCategories = [
            'Sales Revenue',
            'Service Income',
            'Interest Income',
        ];

        foreach ($incomeCategories as $name) {
            TransactionCategory::query()->create([
                'name' => $name,
                'type' => 'income',
                'status' => 'active',
            ]);
        }

        $expenseCategories = [
            'Office Supplies',
            'Utilities',
            'Payroll',
            'Marketing',
        ];

        foreach ($expenseCategories as $name) {
            TransactionCategory::query()->create([
                'name' => $name,
                'type' => 'expense',
                'status' => 'active',
            ]);
        }

        foreach (['Cash', 'Bank Transfer', 'Credit Card', 'Mobile Banking'] as $method) {
            PaymentMethod::query()->create([
                'name' => $method,
                'status' => 'active',
            ]);
        }

        Account::query()->create([
            'account_title' => 'Main Cash Account',
            'account_number' => 'CASH-001',
            'currency_id' => $usd->id,
            'initial_balance' => '5000.0000',
            'current_balance' => '5000.0000',
            'note' => 'Primary petty cash and daily operations',
        ]);

        Account::query()->create([
            'account_title' => 'Business Checking',
            'account_number' => 'BNK-10042',
            'currency_id' => $usd->id,
            'initial_balance' => '25000.0000',
            'current_balance' => '25000.0000',
            'note' => 'Main business bank account',
        ]);

        Account::query()->create([
            'account_title' => 'BDT Savings',
            'account_number' => 'BDT-7781',
            'currency_id' => $bdt->id,
            'initial_balance' => '550000.0000',
            'current_balance' => '550000.0000',
            'note' => 'Local currency operations account',
        ]);

        Contact::query()->create([
            'type' => 'person',
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'phone' => '+1-555-0100',
            'current_balance' => '0.0000',
            'status' => 'active',
        ]);

        Contact::query()->create([
            'type' => 'company',
            'name' => 'Office Depot',
            'email' => 'accounts@officedepot.example',
            'phone' => '+1-555-0200',
            'company' => 'Office Depot',
            'current_balance' => '0.0000',
            'status' => 'active',
        ]);

        SystemSetting::query()->create([
            'system_name' => 'Ledger Engine',
            'default_currency_id' => $usd->id,
            'allow_negative_balances' => false,
        ]);
    }
}
