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
    'max_total_bytes' => (int) env('BACKUP_MAX_TOTAL_BYTES', 100 * 1024 * 1024 * 1024),
    'max_file_bytes' => (int) env('BACKUP_MAX_FILE_BYTES', 6 * 1024 * 1024 * 1024),

    /*
    |--------------------------------------------------------------------------
    | Large backups: drop folder (FTP / Hostinger File Manager)
    |--------------------------------------------------------------------------
    |
    | Copy .zip backups here instead of browser upload when files are multi-GB.
    | Subpath is under storage/app/{directory}/incoming/
    |
    */
    'incoming_subdir' => 'incoming',

    /*
    |--------------------------------------------------------------------------
    | MySQL CLI tools (optional — auto-detected on VPS; shared hosting uses PHP)
    |--------------------------------------------------------------------------
    |
    | Leave MYSQLDUMP_PATH / MYSQL_PATH unset on Hostinger unless support gives
    | you explicit paths. BACKUP_MYSQL_*_METHOD: auto | cli | php
    |
    */
    'mysqldump' => env('MYSQLDUMP_PATH'),
    'mysql' => env('MYSQL_PATH'),
    'mysql_dump_method' => env('BACKUP_MYSQL_DUMP_METHOD', 'auto'),
    'mysql_import_method' => env('BACKUP_MYSQL_IMPORT_METHOD', 'auto'),

    /*
    |--------------------------------------------------------------------------
    | Public uploads included in "full" backups
    |--------------------------------------------------------------------------
    */
    'public_root' => storage_path('app/public'),

];
