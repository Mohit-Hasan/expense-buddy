<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\SystemSetting;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class RouteErrorTracker
{
    /**
     * @var list<int>
     */
    private const TRACKED_STATUSES = [403, 404, 419, 500, 503];

    public function isEnabled(): bool
    {
        try {
            if (! Schema::hasTable('system_settings')) {
                return false;
            }

            return (bool) SystemSetting::query()->value('error_tracking_enabled');
        } catch (\Throwable) {
            return false;
        }
    }

    public function record(int $statusCode, string $path): void
    {
        try {
            if (! Schema::hasTable('system_settings') || ! Schema::hasTable('route_error_counts')) {
                return;
            }

            if (! $this->isEnabled()) {
                return;
            }

            if (! in_array($statusCode, self::TRACKED_STATUSES, true)) {
                return;
            }

            $path = '/'.ltrim(mb_substr($path, 0, 250), '/');
            $now = now();

            $existing = DB::table('route_error_counts')
                ->where('path', $path)
                ->where('status_code', $statusCode)
                ->first();

            if ($existing === null) {
                DB::table('route_error_counts')->insert([
                    'path' => $path,
                    'status_code' => $statusCode,
                    'hit_count' => 1,
                    'last_hit_at' => $now,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                return;
            }

            DB::table('route_error_counts')
                ->where('id', $existing->id)
                ->update([
                    'hit_count' => (int) $existing->hit_count + 1,
                    'last_hit_at' => $now,
                    'updated_at' => $now,
                ]);
        } catch (\Throwable) {
            // Ignore tracking failures during install or before migrations run.
        }
    }

    /**
     * @return Collection<int, object>
     */
    public function topHits(int $limit = 20): Collection
    {
        return DB::table('route_error_counts')
            ->orderByDesc('hit_count')
            ->orderByDesc('last_hit_at')
            ->limit($limit)
            ->get();
    }

    public function paginate(int $perPage = 20): LengthAwarePaginator
    {
        return DB::table('route_error_counts')
            ->orderByDesc('hit_count')
            ->orderByDesc('last_hit_at')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function clear(): void
    {
        DB::table('route_error_counts')->delete();
    }
}
