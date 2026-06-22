<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('transactions')->where('type', 'lending')->update(['type' => 'lending_out']);

        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE transactions MODIFY COLUMN type ENUM('income', 'expense', 'transfer', 'lending_out', 'lending_in', 'lending_repay_in', 'lending_repay_out') NOT NULL");
        }
    }

    public function down(): void
    {
        DB::table('transactions')
            ->whereIn('type', ['lending_in', 'lending_repay_in', 'lending_repay_out'])
            ->update(['type' => 'lending_out']);

        DB::table('transactions')->where('type', 'lending_out')->update(['type' => 'lending']);

        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE transactions MODIFY COLUMN type ENUM('income', 'expense', 'transfer', 'lending') NOT NULL");
        }
    }
};
