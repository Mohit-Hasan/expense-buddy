<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\AppInstall;
use App\Support\ExpenseBuddyTestHarness;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AppInstallTest extends TestCase
{
    #[Test]
    public function it_tracks_installation_lock_file(): void
    {
        AppInstall::clearLock();

        $this->assertFalse(is_file(AppInstall::lockFile()));
        $this->assertFalse(AppInstall::isInstalled());

        AppInstall::markInstalled();

        $this->assertTrue(is_file(AppInstall::lockFile()));
        $this->assertTrue(AppInstall::isInstalled());

        AppInstall::clearLock();
    }

    #[Test]
    public function it_considers_app_installed_when_admin_user_exists(): void
    {
        ExpenseBuddyTestHarness::install();

        $this->assertTrue(AppInstall::isInstalled());
    }
}
