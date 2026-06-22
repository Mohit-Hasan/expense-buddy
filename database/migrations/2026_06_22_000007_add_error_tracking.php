<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('system_settings', function (Blueprint $table): void {
            $table->boolean('error_tracking_enabled')->default(false)->after('backup_last_success_at');
        });

        Schema::create('route_error_counts', function (Blueprint $table): void {
            $table->id();
            $table->string('path', 255);
            $table->unsignedSmallInteger('status_code');
            $table->unsignedBigInteger('hit_count')->default(0);
            $table->timestamp('last_hit_at')->nullable();
            $table->timestamps();

            $table->unique(['path', 'status_code']);
            $table->index(['hit_count', 'last_hit_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('route_error_counts');

        Schema::table('system_settings', function (Blueprint $table): void {
            $table->dropColumn('error_tracking_enabled');
        });
    }
};
