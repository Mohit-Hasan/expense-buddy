<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Currency;
use App\Models\PaymentMethod;
use App\Models\SystemSetting;
use App\Models\TransactionCategory;
use App\Models\User;
use App\Support\AppInstall;
use App\Support\ExpenseBuddyTestHarness;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class InstallServiceTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_installs_admin_currency_settings_and_logo(): void
    {
        AppInstall::clearLock();

        $logo = UploadedFile::fake()->image('brand.png', 100, 100);

        app(\App\Services\InstallService::class)->install([
            'admin_name' => 'Installer Admin',
            'admin_email' => 'installer@test.local',
            'admin_password' => 'secret123',
            'system_name' => 'Installed App',
            'currency_name' => 'Euro',
            'currency_code' => 'EUR',
            'currency_symbol' => '€',
            'allow_negative_balances' => false,
        ], $logo, true);

        $this->assertDatabaseHas('users', ['email' => 'installer@test.local']);
        $this->assertDatabaseHas('currencies', ['code' => 'EUR', 'is_default' => true]);
        $this->assertDatabaseHas('system_settings', ['system_name' => 'Installed App']);

        $settings = SystemSetting::query()->first();
        $this->assertNotNull($settings?->system_logo);
        $this->assertTrue(AppInstall::isInstalled());
    }
}
