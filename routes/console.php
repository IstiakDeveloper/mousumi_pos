<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

// Database backup command
Schedule::command('backup:database')
    ->daily()
    ->appendOutputTo(storage_path('logs/backup.log'));

// এখনি টেস্ট করার জন্য এই কমান্ড রান করুন
Artisan::command('test:backup', function () {
    $this->info('Running backup test...');
    Artisan::call('backup:database');
})->purpose('Test database backup');
