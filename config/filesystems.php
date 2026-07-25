<?php

use App\Support\PublicDiskServe;

$servePublicDiskViaApplication = PublicDiskServe::enabled();

return [

    /*
    |--------------------------------------------------------------------------
    | Default Filesystem Disk
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default filesystem disk that should be used
    | by the framework. The "local" disk, as well as a variety of cloud
    | based disks are available to your application for file storage.
    |
    */

    'default' => env('FILESYSTEM_DISK', 'local'),

    /*
    |--------------------------------------------------------------------------
    | Public disk URL strategy (shared hosting)
    |--------------------------------------------------------------------------
    |
    | When PHP symlink()/link() is disabled (common on shared hosting), set
    | FILESYSTEM_PUBLIC_SERVE=auto or true. Laravel registers /storage/* on
    | the public disk (see disks.public.serve). On VPS with storage:link, use
    | false or auto (detects symlink and skips the route).
    |
    | auto + HOSTINGER_PUBLIC_HTML: always register the route — Apache often
    | cannot follow public/storage even when PHP can read the symlink.
    |
    | Values: auto | true | false
    |
    */

    'public_serve_via_application' => env('FILESYSTEM_PUBLIC_SERVE', 'auto'),

    /*
    |--------------------------------------------------------------------------
    | Upload / media disk
    |--------------------------------------------------------------------------
    |
    | All catalog, store, and document uploads use this disk. Keep "public" for
    | local storage; set to "s3" when using object storage (requires
    | league/flysystem-aws-s3-v3 and AWS_* env vars).
    |
    */

    'media_disk' => env('FILESYSTEM_MEDIA_DISK', 'public'),

    'disks' => [

        'local' => [
            'driver' => 'local',
            'root' => storage_path('app/private'),
            'serve' => false,
            'throw' => false,
            'report' => false,
        ],

        'public' => [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            'url' => rtrim(env('APP_URL', 'http://localhost'), '/').'/storage',
            'visibility' => 'public',
            'serve' => $servePublicDiskViaApplication,
            'throw' => false,
            'report' => false,
        ],

        's3' => [
            'driver' => 's3',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION'),
            'bucket' => env('AWS_BUCKET'),
            'url' => env('AWS_URL'),
            'endpoint' => env('AWS_ENDPOINT'),
            'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
            'throw' => false,
            'report' => false,
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Symbolic Links
    |--------------------------------------------------------------------------
    |
    | Here you may configure the symbolic links that will be created when the
    | `storage:link` Artisan command is executed. The array keys should be
    | the locations of the links and the values should be their targets.
    |
    */

    'links' => [
        public_path('storage') => storage_path('app/public'),
    ],

];
