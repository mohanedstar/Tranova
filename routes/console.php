<?php

use Illuminate\Support\Facades\Schedule;

// Database backup - Daily at 2 AM
Schedule::command('db:backup --compress --keep=7')
    ->dailyAt('02:00')
    ->appendOutputTo(storage_path('logs/backup.log'));

// Clean old backups - Weekly on Sunday at 3 AM
Schedule::command('db:backup --keep=7')
    ->weeklyOn(0, '03:00')
    ->appendOutputTo(storage_path('logs/backup.log'));
