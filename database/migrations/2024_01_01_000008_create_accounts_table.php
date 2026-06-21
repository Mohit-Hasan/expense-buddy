<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accounts', function (Blueprint $table): void {
            $table->id();
            $table->string('account_title');
            $table->string('account_number')->nullable();
            $table->foreignId('currency_id')
                ->constrained('currencies')
                ->restrictOnDelete();
            $table->decimal('initial_balance', 15, 4)->default('0.0000');
            $table->decimal('current_balance', 15, 4)->default('0.0000');
            $table->text('note')->nullable();
            $table->timestamps();

            $table->index('currency_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accounts');
    }
};
