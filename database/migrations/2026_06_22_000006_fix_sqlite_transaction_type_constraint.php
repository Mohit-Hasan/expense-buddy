<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'sqlite') {
            return;
        }

        $this->rebuildTransactionsTable();
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'sqlite') {
            return;
        }

        DB::table('transactions')
            ->whereIn('type', ['lending_in', 'lending_repay_in', 'lending_repay_out'])
            ->update(['type' => 'lending_out']);

        DB::table('transactions')->where('type', 'lending_out')->update(['type' => 'lending']);
    }

    private function rebuildTransactionsTable(): void
    {
        Schema::disableForeignKeyConstraints();

        if (Schema::hasTable('transactions_new')) {
            Schema::drop('transactions_new');
        }

        Schema::create('transactions_new', function (Blueprint $table): void {
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
                ->on('transactions_new')
                ->nullOnDelete();

            $table->index(['account_id', 'transaction_date']);
            $table->index(['type', 'transaction_date']);
            $table->index(['contact_id', 'type']);
            $table->index('transaction_date');
            $table->index('transfer_reference_id');
        });

        DB::table('transactions')
            ->orderBy('id')
            ->chunkById(200, function ($rows): void {
                foreach ($rows as $row) {
                    $type = (string) $row->type;

                    if ($type === 'lending') {
                        $type = 'lending_out';
                    }

                    DB::table('transactions_new')->insert([
                        'id' => $row->id,
                        'account_id' => $row->account_id,
                        'type' => $type,
                        'category_id' => $row->category_id,
                        'payment_method_id' => $row->payment_method_id,
                        'currency_id' => $row->currency_id,
                        'amount' => $row->amount,
                        'rate_at_transaction' => $row->rate_at_transaction,
                        'contact_id' => $row->contact_id,
                        'transaction_date' => $row->transaction_date,
                        'reference' => $row->reference,
                        'description' => $row->description,
                        'attachment' => $row->attachment,
                        'transfer_reference_id' => $row->transfer_reference_id,
                        'created_at' => $row->created_at,
                        'updated_at' => $row->updated_at,
                    ]);
                }
            });

        Schema::drop('transactions');
        Schema::rename('transactions_new', 'transactions');

        Schema::enableForeignKeyConstraints();
    }
};
