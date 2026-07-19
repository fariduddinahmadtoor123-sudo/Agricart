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
    'archive' => [
        'max_entries' => (int) env('BACKUP_ARCHIVE_MAX_ENTRIES', 100000),
        'max_entry_bytes' => (int) env('BACKUP_ARCHIVE_MAX_ENTRY_BYTES', 2 * 1024 * 1024 * 1024),
        'max_uncompressed_bytes' => (int) env('BACKUP_ARCHIVE_MAX_UNCOMPRESSED_BYTES', 20 * 1024 * 1024 * 1024),
        'max_compression_ratio' => (float) env('BACKUP_ARCHIVE_MAX_COMPRESSION_RATIO', 200),
        'max_manifest_bytes' => (int) env('BACKUP_ARCHIVE_MAX_MANIFEST_BYTES', 64 * 1024),
    ],

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

    /*
    |--------------------------------------------------------------------------
    | After restore: keep local logins + bootstrap admin
    |--------------------------------------------------------------------------
    |
    | Full restore replaces the database. When preserve_local_logins is true,
    | Production defaults to authoritative local replacement: captured local
    | login rows replace same-email archive rows and missing rows are restored.
    | Local development keeps the safer merge-missing behavior.
    |
    | bootstrap_admin is always ensured after restore when email/password are set
    | (default local admin for Laragon / Hostinger testing).
    |
    */
    'preserve_local_logins' => filter_var(
        env('BACKUP_PRESERVE_LOCAL_LOGINS', true),
        FILTER_VALIDATE_BOOLEAN,
    ),
    'production_login_mode' => env('BACKUP_PRODUCTION_LOGIN_MODE', 'authoritative'),
    'bootstrap_admin' => [
        // Empty by default — never ship a known password. Set in local .env only.
        'email' => env('BACKUP_BOOTSTRAP_ADMIN_EMAIL'),
        'password' => env('BACKUP_BOOTSTRAP_ADMIN_PASSWORD'),
        'name' => env('BACKUP_BOOTSTRAP_ADMIN_NAME', 'Super Admin'),
        // When true (local only), reset bootstrap admin password after restore if email already exists.
        'force_password_on_local' => filter_var(
            env('BACKUP_BOOTSTRAP_ADMIN_FORCE_LOCAL', true),
            FILTER_VALIDATE_BOOLEAN,
        ),
    ],

];
