<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Models\DatabaseSession;
use App\Models\User;
use App\Support\ExpenseBuddyTestHarness;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ActiveSessionsTest extends TestCase
{
    #[Test]
    public function security_page_lists_active_sessions(): void
    {
        ExpenseBuddyTestHarness::install();

        $user = User::query()->where('email', ExpenseBuddyTestHarness::ADMIN_EMAIL)->firstOrFail();
        $currentSessionId = str_repeat('a', 40);

        DatabaseSession::query()->create([
            'id' => $currentSessionId,
            'user_id' => $user->id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X) AppleWebKit/537.36 Chrome/120.0.0.0 Safari/537.36',
            'payload' => base64_encode(serialize([])),
            'last_activity' => now()->getTimestamp(),
        ]);

        DatabaseSession::query()->create([
            'id' => 'remote-session',
            'user_id' => $user->id,
            'ip_address' => '203.0.113.10',
            'user_agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) Safari/604.1',
            'payload' => base64_encode(serialize([])),
            'last_activity' => now()->subMinutes(10)->getTimestamp(),
        ]);

        $this->actingAs($user)
            ->withCookie(config('session.cookie'), $currentSessionId)
            ->withSession(['auth_session_version' => $user->session_version])
            ->get(route('account.security'))
            ->assertOk()
            ->assertSee('Where You\'re Logged In')
            ->assertSee('This device')
            ->assertSee('Chrome on macOS')
            ->assertSee('Safari on iPhone')
            ->assertSee('203.0.113.10')
            ->assertSee('Log out of all other sessions');
    }

    #[Test]
    public function user_can_log_out_another_session(): void
    {
        ExpenseBuddyTestHarness::install();

        $user = User::query()->where('email', ExpenseBuddyTestHarness::ADMIN_EMAIL)->firstOrFail();
        $currentSessionId = str_repeat('b', 40);

        DatabaseSession::query()->create([
            'id' => $currentSessionId,
            'user_id' => $user->id,
            'payload' => base64_encode(serialize([])),
            'last_activity' => now()->getTimestamp(),
        ]);

        DatabaseSession::query()->create([
            'id' => 'remote-session',
            'user_id' => $user->id,
            'payload' => base64_encode(serialize([])),
            'last_activity' => now()->getTimestamp(),
        ]);

        $this->actingAs($user)
            ->withCookie(config('session.cookie'), $currentSessionId)
            ->withSession(['auth_session_version' => $user->session_version])
            ->delete(route('account.security.sessions.destroy', 'remote-session'))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('sessions', ['id' => 'remote-session']);
    }

    #[Test]
    public function logging_out_all_other_sessions_invalidates_stale_session_versions(): void
    {
        ExpenseBuddyTestHarness::install();

        $user = User::query()->where('email', ExpenseBuddyTestHarness::ADMIN_EMAIL)->firstOrFail();
        $currentSessionId = str_repeat('c', 40);
        $remoteSessionId = str_repeat('d', 40);

        DatabaseSession::query()->create([
            'id' => $currentSessionId,
            'user_id' => $user->id,
            'payload' => base64_encode(serialize([])),
            'last_activity' => now()->getTimestamp(),
        ]);

        DatabaseSession::query()->create([
            'id' => $remoteSessionId,
            'user_id' => $user->id,
            'payload' => base64_encode(serialize([])),
            'last_activity' => now()->getTimestamp(),
        ]);

        $this->actingAs($user)
            ->withCookie(config('session.cookie'), $currentSessionId)
            ->withSession(['auth_session_version' => $user->session_version])
            ->post(route('account.security.sessions.logout-others'))
            ->assertRedirect()
            ->assertSessionHas('success');

        $user->refresh();
        $this->assertSame(2, $user->session_version);

        $this->withCookie(config('session.cookie'), $remoteSessionId)
            ->withSession([
                'auth_session_version' => 1,
                'login_web' => $user->id,
            ])
            ->get(route('dashboard'))
            ->assertRedirect(route('login'));
    }
}
