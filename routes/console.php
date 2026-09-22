<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('app:record-scheduler-heartbeat')
    ->everyMinute()
    ->withoutOverlapping();

Schedule::command('consultation:retry-notifications')
    ->everyFiveMinutes()
    ->withoutOverlapping();

Schedule::command('agent-rafeeq:sync')
    ->hourly()
    ->environments('production')
    ->when(fn (): bool => (bool) config('services.agent_rafeeq_sync.enabled'))
    ->withoutOverlapping(55);
