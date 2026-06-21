<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('currencies', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('code', 10);
            $table->string('symbol', 10);
            $table->decimal('exchange_rate', 15, 4)->default('1.0000');
            $table->boolean('is_default')->default(false);
            $table->timestamps();

            $table->unique('code');
            $table->index('is_default');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('currencies');
    }
};
