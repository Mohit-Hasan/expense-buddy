<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transaction_invoices', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('transaction_id')
                ->constrained('transactions')
                ->cascadeOnDelete();
            $table->string('invoice_number')->unique();
            $table->string('public_token', 64)->unique();
            $table->timestamp('expires_at')->nullable();
            $table->boolean('is_public')->default(false);
            $table->timestamps();

            $table->index(['public_token', 'expires_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transaction_invoices');
    }
};
