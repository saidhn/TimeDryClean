<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

// Recurring subscription billing: checks daily for any client subscription
// whose period has ended and charges/renews it.
Schedule::command('subscriptions:process-renewals')->daily();
