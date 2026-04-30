<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('cch:sync-sphere-users')
    ->hourly()
    ->withoutOverlapping();

Schedule::command('cch:sync-item-data')
    ->dailyAt('23:00')
    ->withoutOverlapping();

Schedule::command('cch:check-due-dates')
    ->dailyAt('08:00') // Kirim email jam 8 pagi
    ->withoutOverlapping();
