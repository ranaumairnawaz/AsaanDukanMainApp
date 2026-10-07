<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Run every hour to suspend expired stores (trials & subscriptions)
Schedule::command('stores:deactivate-expired')->hourly();

// Run daily at 9am PKT to send subscription expiry WhatsApp alerts and suspend overdue stores
Schedule::command('subscriptions:monitor')->dailyAt('09:00');
