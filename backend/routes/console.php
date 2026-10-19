<?php

use App\Jobs\AbandonedBookingReminderJob;
use App\Jobs\ExpireBookingHoldsJob;
use App\Jobs\RunDatabaseBackupJob;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::job(new ExpireBookingHoldsJob)->everyMinute();
Schedule::job(new AbandonedBookingReminderJob)->everyFifteenMinutes();
Schedule::job(new RunDatabaseBackupJob)->dailyAt('02:30');
