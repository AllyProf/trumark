<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

use Illuminate\Support\Facades\Schedule;
Schedule::command('app:notify-follow-ups')->dailyAt('08:00');
Schedule::command('surveys:send-automated')->everyMinute();
Schedule::command('followups:send-reminders')->everyMinute();
