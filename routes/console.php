<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('imports:cleanup --days=30')
    ->dailyAt('02:00');

// Reminder PT3 berdasarkan due date milestone Kurva-S.
Schedule::command('webhook:publish-stale-project-reminders --grace-days=0')
    ->dailyAt('08:00');
