<?php

return [

    /*
    |---------------------------------------------------------------------------
    | Class Namespace
    |---------------------------------------------------------------------------
    */

    'class_namespace' => 'App\\Livewire',

    /*
    |---------------------------------------------------------------------------
    | View Path
    |---------------------------------------------------------------------------
    */

    'view_path' => resource_path('views/livewire'),

    'layout' => 'components.layouts.app',

    'lazy_placeholder' => null,

    /*
    |---------------------------------------------------------------------------
    | Temporary File Uploads
    |---------------------------------------------------------------------------
    |
    | Global rules apply to EVERY Livewire upload (category/product images,
    | CNIC scans, etc.) — do NOT restrict to zip here. Backup zip uploads go
    | through BackupUploadController (direct HTTP), which validates mimes:zip
    | and the backup size limit itself.
    */

    'temporary_file_upload' => [
        // Keep temp uploads off the public disk so /storage/livewire-tmp is not web-reachable.
        // max is KB (Laravel). Keep room for portable .ags archives (categories/media).
        'disk' => 'local',
        'rules' => [
            'required',
            'file',
            'max:1024000',
            'mimetypes:image/jpeg,image/png,image/webp,image/gif,image/bmp,image/avif,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document,application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/zip,application/x-zip-compressed,application/octet-stream,text/plain,text/csv',
        ],
        'directory' => 'livewire-tmp',
        'middleware' => null,
        'preview_mimes' => [
            'png', 'gif', 'bmp', 'svg', 'wav', 'mp4',
            'mov', 'avi', 'wmv', 'mp3', 'm4a',
            'jpg', 'jpeg', 'jfif', 'pjpeg', 'mpga', 'webp', 'wma', 'avif',
        ],
        // Minutes — large category archives need longer than the Livewire default.
        'max_upload_time' => 120,
        'cleanup' => true,
    ],

    'render_on_redirect' => false,

    'legacy_model_binding' => false,

    'inject_assets' => true,

    'navigate' => [
        'show_progress_bar' => true,
        'progress_bar_color' => '#2299dd',
    ],

    'inject_morph_markers' => true,

    'smart_wire_keys' => false,

    'pagination_theme' => 'tailwind',

    'release_token' => 'a',
];
