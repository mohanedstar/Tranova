<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Backup Configuration
    |--------------------------------------------------------------------------
    */

    // Backup directory (relative to storage_path)
    'path' => 'app/backups',

    // Database connection to backup (null = default)
    'connection' => env('DB_CONNECTION', 'mysql'),

    // Compress backups with gzip
    'compress' => env('BACKUP_COMPRESS', true),

    // Number of backups to keep (0 = keep all)
    'keep' => env('BACKUP_KEEP', 7),

    // Upload to cloud storage
    'upload' => [
        'enabled' => env('BACKUP_UPLOAD', false),
        'disk' => env('BACKUP_DISK', 's3'),
        'path' => 'backups/database',
    ],

    // Email notification
    'notification' => [
        'enabled' => env('BACKUP_NOTIFY', false),
        'email' => env('BACKUP_NOTIFY_EMAIL', 'admin@trinova.com'),
    ],

    // Schedule (for Laravel Scheduler)
    'schedule' => [
        'enabled' => env('BACKUP_SCHEDULE', true),
        'cron' => '0 2 * * *', // Daily at 2 AM
    ],

    // File naming
    'filename' => [
        'prefix' => 'trinova_backup',
        'format' => 'Y-m-d_H-i-s',
    ],

    // Excluded tables (optional)
    'exclude_tables' => [
        // 'sessions',
        // 'cache',
        // 'jobs',
    ],
];
