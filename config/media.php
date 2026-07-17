<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Image optimization (catalog / storefront scale)
    |--------------------------------------------------------------------------
    |
    | Uploads are resized and stored as WebP so a large catalog (~100k images)
    | stays fast to deliver. Legal / ID scans use the "document" preset and
    | are stored unchanged.
    |
    */

    'disk' => 'public',

    'presets' => [
        'catalog' => [
            'max_width' => 1600,
            'max_height' => 1600,
            'quality' => 82,
            'convert_webp' => true,
        ],
        'gallery' => [
            'max_width' => 1600,
            'max_height' => 1600,
            'quality' => 80,
            'convert_webp' => true,
        ],
        'logo' => [
            'max_width' => 800,
            'max_height' => 800,
            'quality' => 85,
            'convert_webp' => true,
        ],
        'avatar' => [
            'max_width' => 512,
            'max_height' => 512,
            'quality' => 80,
            'convert_webp' => true,
        ],
        'document' => [
            'max_width' => null,
            'max_height' => null,
            'quality' => null,
            'convert_webp' => false,
        ],
    ],

    'directory_presets' => [
        'categories' => 'catalog',
        'brands' => 'catalog',
        'products' => 'catalog',
        'products/gallery' => 'gallery',
        'store' => 'logo',
        'customers/photos' => 'avatar',
        'suppliers/profile-photos' => 'avatar',
        'users/profile-photos' => 'avatar',
    ],

];
