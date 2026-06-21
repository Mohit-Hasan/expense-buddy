<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Support\ExpenseBuddyTestHarness;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class LoginTest extends TestCase
{
    #[Test]
    public function guest_can_view_login_page_when_installed(): void
    {
        ExpenseBuddyTestHarness::install();

        $this->get('/login')->assertOk()->assertSee('Sign In');
    }

    #[Test]
    public function admin_can_login_and_reach_dashboard(): void
    {
        ExpenseBuddyTestHarness::install();

        $response = $this->post('/login', [
            'email' => ExpenseBuddyTestHarness::ADMIN_EMAIL,
            'password' => ExpenseBuddyTestHarness::ADMIN_PASSWORD,
        ]);

        $response->assertRedirect(route('dashboard'));

        $this->get('/')->assertOk();
    }

    #[Test]
    public function invalid_credentials_are_rejected(): void
    {
        ExpenseBuddyTestHarness::install();

        $this->post('/login', [
            'email' => ExpenseBuddyTestHarness::ADMIN_EMAIL,
            'password' => 'wrong-password',
        ])->assertSessionHasErrors();
    }

    #[Test]
    public function guest_is_redirected_to_installer_when_app_is_not_installed(): void
    {
        ExpenseBuddyTestHarness::prepareUninstalled();

        $this->get('/login')->assertRedirect('/install/');
    }
}
