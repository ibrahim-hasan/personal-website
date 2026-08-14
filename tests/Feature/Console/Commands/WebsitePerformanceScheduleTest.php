<?php

namespace Tests\Feature\Console\Commands;

use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;
use Tests\TestCase;

class WebsitePerformanceScheduleTest extends TestCase
{
    public function test_the_weekly_performance_report_runs_once_on_the_production_scheduler(): void
    {
        $this->artisan('schedule:list')
            ->expectsOutputToContain('website:performance-report')
            ->assertSuccessful();

        $event = $this->websitePerformanceEvent();

        $this->assertSame('0 9 * * 1', $event->expression);
        $this->assertSame('Asia/Riyadh', $event->timezone);
        $this->assertSame(['production'], $event->environments);
        $this->assertSame('website-performance-weekly-report', $event->description);
        $this->assertTrue($event->withoutOverlapping);
        $this->assertSame(120, $event->expiresAt);
        $this->assertTrue($event->onOneServer);
        $this->assertTrue($event->runInBackground);

        $application = file_get_contents(base_path('bootstrap/app.php'));
        $consoleRoutes = file_get_contents(base_path('routes/console.php'));

        $this->assertNotFalse($application);
        $this->assertNotFalse($consoleRoutes);
        $this->assertStringContainsString("\$schedule->command('website:performance-report')", $application);
        $this->assertStringNotContainsString('website:performance-report', $consoleRoutes);
    }

    private function websitePerformanceEvent(): Event
    {
        foreach (app(Schedule::class)->events() as $event) {
            if (str_contains((string) $event->command, 'website:performance-report')) {
                return $event;
            }
        }

        $this->fail('The website performance report is not scheduled.');
    }
}
