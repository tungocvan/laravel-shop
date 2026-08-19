<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

if (filter_var(env('FACEBOOK_SCHEDULER_ENABLED', false), FILTER_VALIDATE_BOOL)) {
    Schedule::command('facebook:dispatch-scheduled')
        ->everyMinute()
        ->withoutOverlapping();
}

Schedule::command('system:cloud-backup')
    ->everyMinute()
    ->withoutOverlapping();
