<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\DatabaseSession;
use App\Models\User;
use App\Services\UserSessionManager;
use App\Support\ExpenseBuddyTestHarness;
use Illuminate\Http\Request;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UserSessionManagerTest extends TestCase
{
    #[Test]
    public function logging_out_other_devices_bumps_session_version(): void
    {
        ExpenseBuddyTestHarness::install();

        $user = User::query()->where('email', ExpenseBuddyTestHarness::ADMIN_EMAIL)->firstOrFail();
        $manager = app(UserSessionManager::class);

        $this->actingAs($user);
        $request = Request::create('/account/security', 'GET');
        $request->setLaravelSession(session()->driver());

        session()->put('auth_session_version', $user->session_version);

        DatabaseSession::query()->create([
            'id' => 'remote-session',
            'user_id' => $user->id,
            'payload' => base64_encode(serialize([])),
            'last_activity' => now()->getTimestamp(),
        ]);

        $manager->logoutOtherDevices($user, $request);

        $user->refresh();

        $this->assertSame(2, $user->session_version);
        $this->assertSame(2, session('auth_session_version'));
        $this->assertDatabaseMissing('sessions', ['id' => 'remote-session']);
    }
}
