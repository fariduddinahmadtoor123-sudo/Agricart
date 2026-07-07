<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Backup storage
    |--------------------------------------------------------------------------
    */
    'disk' => 'local',
    'directory' => 'agricart-backups',

    /*
    |--------------------------------------------------------------------------
    | Retention & size limits
    |--------------------------------------------------------------------------
    */
    'retention_count' => (int) env('BACKUP_RETENTION_COUNT', 10),
    'max_total_bytes' => (int) env('BACKUP_MAX_TOTAL_BYTES', 5 * 1024 * 1024 * 1024),
    'max_file_bytes' => (int) env('BACKUP_MAX_FILE_BYTES', 2 * 1024 * 1024 * 1024),

    /*
    |--------------------------------------------------------------------------
    | MySQL tools (Laragon example: C:\laragon\bin\mysql\mysql-8.4\bin\mysqldump.exe)
    |--------------------------------------------------------------------------
    */
    'mysqldump' => env('MYSQLDUMP_PATH', 'mysqldump'),
    'mysql' => env('MYSQL_PATH', 'mysql'),

    /*
    |--------------------------------------------------------------------------
    | Public uploads included in "full" backups
    |--------------------------------------------------------------------------
    */
    'public_root' => storage_path('app/public'),

];
