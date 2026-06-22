<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\DatabaseSession;
use App\Models\User;
use App\Services\ActiveSessionService;
use App\Support\ExpenseBuddyTestHarness;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ActiveSessionServiceTest extends TestCase
{

    #[Test]
    public function it_lists_only_active_sessions_for_the_user(): void
    {
        ExpenseBuddyTestHarness::install();
        $user = User::query()->where('email', ExpenseBuddyTestHarness::ADMIN_EMAIL)->firstOrFail();

        $this->seedSession($user->id, 'current-session', now()->getTimestamp(), '127.0.0.1', 'Mozilla/5.0 Chrome/120.0.0.0');
        $this->seedSession($user->id, 'other-session', now()->subHour()->getTimestamp(), '10.0.0.1', 'Mozilla/5.0 Firefox');
        $this->seedSession($user->id, 'expired-session', now()->subDays(3)->getTimestamp(), '10.0.0.2', 'Mozilla/5.0 Safari');

        $sessions = app(ActiveSessionService::class)->activeForUser($user, 'current-session');

        $this->assertCount(2, $sessions);
        $this->assertTrue($sessions->firstWhere('id', 'current-session')['is_current']);
        $this->assertSame('Chrome', $sessions->firstWhere('id', 'current-session')['device_label']);
        $this->assertNull($sessions->firstWhere('id', 'expired-session'));
    }

    #[Test]
    public function it_can_log_out_a_single_other_session(): void
    {
        ExpenseBuddyTestHarness::install();
        $user = User::query()->where('email', ExpenseBuddyTestHarness::ADMIN_EMAIL)->firstOrFail();
        $service = app(ActiveSessionService::class);

        $this->seedSession($user->id, 'current-session', now()->getTimestamp());
        $this->seedSession($user->id, 'other-session', now()->getTimestamp());

        $this->assertTrue($service->destroyForUser($user, 'other-session', 'current-session'));
        $this->assertFalse($service->destroyForUser($user, 'current-session', 'current-session'));
        $this->assertDatabaseMissing('sessions', ['id' => 'other-session']);
        $this->assertDatabaseHas('sessions', ['id' => 'current-session']);
    }

    #[Test]
    public function it_can_log_out_all_other_sessions(): void
    {
        ExpenseBuddyTestHarness::install();
        $user = User::query()->where('email', ExpenseBuddyTestHarness::ADMIN_EMAIL)->firstOrFail();
        $service = app(ActiveSessionService::class);

        $this->seedSession($user->id, 'current-session', now()->getTimestamp());
        $this->seedSession($user->id, 'other-session-1', now()->getTimestamp());
        $this->seedSession($user->id, 'other-session-2', now()->getTimestamp());

        $removed = $service->destroyAllExcept($user, 'current-session');

        $this->assertSame(2, $removed);
        $this->assertDatabaseHas('sessions', ['id' => 'current-session']);
        $this->assertDatabaseMissing('sessions', ['id' => 'other-session-1']);
        $this->assertDatabaseMissing('sessions', ['id' => 'other-session-2']);
    }

    private function seedSession(
        int $userId,
        string $id,
        int $lastActivity,
        ?string $ip = null,
        ?string $userAgent = null,
    ): void {
        DatabaseSession::query()->create([
            'id' => $id,
            'user_id' => $userId,
            'ip_address' => $ip,
            'user_agent' => $userAgent,
            'payload' => base64_encode(serialize([])),
            'last_activity' => $lastActivity,
        ]);
    }
}
