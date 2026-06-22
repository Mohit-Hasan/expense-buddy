<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('account_id')
                ->constrained('accounts')
                ->restrictOnDelete();
            $table->string('type', 30);
            $table->foreignId('category_id')
                ->nullable()
                ->constrained('transaction_categories')
                ->nullOnDelete();
            $table->foreignId('payment_method_id')
                ->constrained('payment_methods')
                ->restrictOnDelete();
            $table->foreignId('currency_id')
                ->constrained('currencies')
                ->restrictOnDelete();
            $table->decimal('amount', 15, 4);
            $table->decimal('rate_at_transaction', 15, 4);
            $table->foreignId('contact_id')
                ->nullable()
                ->constrained('contacts')
                ->nullOnDelete();
            $table->date('transaction_date');
            $table->string('reference')->nullable();
            $table->text('description')->nullable();
            $table->string('attachment')->nullable();
            $table->unsignedBigInteger('transfer_reference_id')->nullable();
            $table->timestamps();

            $table->foreign('transfer_reference_id')
                ->references('id')
                ->on('transactions')
                ->nullOnDelete();

            $table->index(['account_id', 'transaction_date']);
            $table->index(['type', 'transaction_date']);
            $table->index(['contact_id', 'type']);
            $table->index('transaction_date');
            $table->index('transfer_reference_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
