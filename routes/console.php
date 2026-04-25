<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Schedule nightly exchange rate updates
Schedule::command('exchange-rates:update')
    ->dailyAt('02:00')
    ->timezone('Africa/Kampala')
    ->appendOutputTo(storage_path('logs/exchange-rates.log'))
    ->description('Update exchange rates from external API every night');
