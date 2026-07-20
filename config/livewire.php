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
        'disk' => null,
        'rules' => ['required', 'file', 'max:12288'],
        'directory' => null,
        'middleware' => null,
        'preview_mimes' => [
            'png', 'gif', 'bmp', 'svg', 'wav', 'mp4',
            'mov', 'avi', 'wmv', 'mp3', 'm4a',
            'jpg', 'jpeg', 'jfif', 'pjpeg', 'mpga', 'webp', 'wma', 'avif',
        ],
        'max_upload_time' => 30,
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
