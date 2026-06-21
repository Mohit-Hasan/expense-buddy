<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\SystemSetting;
use App\Support\AppInstall;
use App\Support\Brand;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class BrandTest extends TestCase
{
    #[Test]
    public function it_exposes_default_brand_name_and_tagline(): void
    {
        $this->assertSame('ExpenseBuddy', Brand::name());
        $this->assertSame('Your Personal Finance Companion', Brand::tagline());
        $this->assertStringContainsString('ExpenseBuddy', Brand::fullName());
    }

    #[Test]
    public function it_uses_system_settings_name_when_available(): void
    {
        $settings = new SystemSetting(['system_name' => 'Acme Finance']);

        $this->assertSame('Acme Finance', Brand::appName($settings));
    }
}
