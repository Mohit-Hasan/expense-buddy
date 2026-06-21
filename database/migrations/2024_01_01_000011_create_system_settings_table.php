<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('system_settings', function (Blueprint $table): void {
            $table->id();
            $table->string('system_name')->default('ExpenseBuddy');
            $table->string('system_logo')->nullable();
            $table->foreignId('default_currency_id')
                ->nullable()
                ->constrained('currencies')
                ->nullOnDelete();
            $table->boolean('allow_negative_balances')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('system_settings');
    }
};
