<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\DatabaseSession;
use App\Models\User;
use App\Support\UserAgentSummary;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

final class ActiveSessionService
{
    /**
     * @return Collection<int, array{
     *     id: string,
     *     device_label: string,
     *     ip_address: string|null,
     *     user_agent: string|null,
     *     last_active_at: Carbon,
     *     is_current: bool
     * }>
     */
    public function activeForUser(User $user, string $currentSessionId): Collection
    {
        return DatabaseSession::query()
            ->where('user_id', $user->id)
            ->where('last_activity', '>=', $this->activityCutoff())
            ->orderByDesc('last_activity')
            ->get()
            ->map(fn (DatabaseSession $session): array => $this->format($session, $currentSessionId));
    }

    public function destroyForUser(User $user, string $sessionId, string $currentSessionId): bool
    {
        if ($sessionId === $currentSessionId) {
            return false;
        }

        return DatabaseSession::query()
            ->where('user_id', $user->id)
            ->where('id', $sessionId)
            ->delete() > 0;
    }

    public function destroyAllExcept(User $user, string $currentSessionId): int
    {
        return DatabaseSession::query()
            ->where('user_id', $user->id)
            ->where('id', '!=', $currentSessionId)
            ->delete();
    }

    /**
     * @return array{
     *     id: string,
     *     device_label: string,
     *     ip_address: string|null,
     *     user_agent: string|null,
     *     last_active_at: Carbon,
     *     is_current: bool
     * }
     */
    private function format(DatabaseSession $session, string $currentSessionId): array
    {
        return [
            'id' => $session->id,
            'device_label' => UserAgentSummary::label($session->user_agent),
            'ip_address' => $session->ip_address,
            'user_agent' => $session->user_agent,
            'last_active_at' => $session->lastActiveAt(),
            'is_current' => $session->id === $currentSessionId,
        ];
    }

    private function activityCutoff(): int
    {
        $lifetimeMinutes = (int) config('session.lifetime', 120);

        return now()->subMinutes($lifetimeMinutes)->getTimestamp();
    }
}
