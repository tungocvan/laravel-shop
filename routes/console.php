<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

if (config('services.facebook.scheduler_enabled', false)) {
    Schedule::command('facebook:dispatch-scheduled')
        ->everyMinute()
        ->withoutOverlapping();
}

Schedule::command('system:cloud-backup')
    ->everyMinute()
    ->withoutOverlapping();
