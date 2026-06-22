<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\SystemSetting;
use App\Services\RouteErrorTracker;
use App\Support\ExpenseBuddyTestHarness;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RouteErrorTrackerTest extends TestCase
{
    #[Test]
    public function it_records_hits_when_tracking_is_enabled(): void
    {
        ExpenseBuddyTestHarness::install(withDemo: false);

        SystemSetting::query()->first()?->update(['error_tracking_enabled' => true]);

        $tracker = app(RouteErrorTracker::class);
        $tracker->record(404, '/missing-page');
        $tracker->record(404, '/missing-page');

        $row = DB::table('route_error_counts')->where('path', '/missing-page')->first();

        $this->assertNotNull($row);
        $this->assertSame(404, (int) $row->status_code);
        $this->assertSame(2, (int) $row->hit_count);
    }

    #[Test]
    public function it_skips_recording_when_tracking_is_disabled(): void
    {
        ExpenseBuddyTestHarness::install(withDemo: false);

        SystemSetting::query()->first()?->update(['error_tracking_enabled' => false]);

        app(RouteErrorTracker::class)->record(404, '/ignored');

        $this->assertDatabaseMissing('route_error_counts', [
            'path' => '/ignored',
            'status_code' => 404,
        ]);
    }
}
