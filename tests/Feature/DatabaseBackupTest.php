<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\SystemSetting;
use App\Services\DatabaseBackupService;
use App\Support\ExpenseBuddyTestHarness;
use Illuminate\Support\Facades\Artisan;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DatabaseBackupTest extends TestCase
{
    #[Test]
    public function scheduler_check_updates_even_when_backup_is_not_due(): void
    {
        ExpenseBuddyTestHarness::install(withDemo: true);

        $settings = SystemSetting::query()->firstOrFail();
        $settings->update([
            'backup_enabled' => false,
            'backup_last_run_at' => null,
        ]);

        Artisan::call('expensebuddy:run-scheduled-backup');

        $settings->refresh();
        $this->assertNotNull($settings->backup_last_run_at);
        $this->assertNull($settings->backup_last_success_at);
    }

    #[Test]
    public function daily_backup_runs_on_first_scheduler_check(): void
    {
        ExpenseBuddyTestHarness::install(withDemo: true);

        $settings = SystemSetting::query()->firstOrFail();
        $settings->update([
            'backup_enabled' => true,
            'backup_frequency' => 'daily',
            'backup_email' => 'backup@example.com',
            'mail_driver' => 'array',
            'mail_from_address' => 'noreply@example.com',
            'backup_last_run_at' => null,
            'backup_last_success_at' => null,
        ]);

        Artisan::call('expensebuddy:run-scheduled-backup');

        $settings->refresh();
        $this->assertNotNull($settings->backup_last_run_at);
        $this->assertNotNull($settings->backup_last_success_at);
    }

    #[Test]
    public function weekly_backup_runs_immediately_when_never_succeeded_before(): void
    {
        ExpenseBuddyTestHarness::install(withDemo: true);

        $settings = SystemSetting::query()->firstOrFail();
        $settings->update([
            'backup_enabled' => true,
            'backup_frequency' => 'weekly',
            'backup_day' => 0,
            'backup_email' => 'backup@example.com',
            'mail_driver' => 'array',
            'mail_from_address' => 'noreply@example.com',
            'backup_last_success_at' => null,
        ]);

        $service = app(DatabaseBackupService::class);
        $this->assertTrue($service->shouldRunScheduledBackup($settings->fresh()));
    }

    #[Test]
    public function administrator_can_trigger_manual_email_backup(): void
    {
        $admin = ExpenseBuddyTestHarness::install(withDemo: true);

        SystemSetting::query()->firstOrFail()->update([
            'backup_email' => 'backup@example.com',
            'mail_driver' => 'array',
            'mail_from_address' => 'noreply@example.com',
        ]);

        $this->actingAs($admin)
            ->post('/admin/backup/run')
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertNotNull(SystemSetting::query()->value('backup_last_success_at'));
    }
}
