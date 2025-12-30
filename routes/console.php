<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Jalankan command ini setiap hari pada pukul 02:00 pagi
Schedule::call(function () {
    Artisan::call('app:process-attendance');
})->dailyAt('02:00');
