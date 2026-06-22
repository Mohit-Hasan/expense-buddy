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
            $table->boolean('backup_enabled')->default(false)->after('allow_negative_balances');
            $table->string('backup_frequency', 20)->default('weekly')->after('backup_enabled');
            $table->unsignedTinyInteger('backup_day')->default(1)->after('backup_frequency');
            $table->string('backup_email')->nullable()->after('backup_day');
            $table->timestamp('backup_last_run_at')->nullable()->after('backup_email');
            $table->timestamp('backup_last_success_at')->nullable()->after('backup_last_run_at');
        });
    }

    public function down(): void
    {
        Schema::table('system_settings', function (Blueprint $table): void {
            $table->dropColumn([
                'backup_enabled',
                'backup_frequency',
                'backup_day',
                'backup_email',
                'backup_last_run_at',
                'backup_last_success_at',
            ]);
        });
    }
};
