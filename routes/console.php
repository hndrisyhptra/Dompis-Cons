<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');


Schedule::command('imports:cleanup --days=30')
    ->dailyAt('02:00');

// Publish event pengingat (role PM): project di-assign tapi tidak ada update >1 hari.
Schedule::command('webhook:publish-stale-project-reminders --hours=24')
    ->dailyAt('08:00');
