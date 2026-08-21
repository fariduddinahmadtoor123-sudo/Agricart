<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Image optimization (catalog / storefront scale)
    |--------------------------------------------------------------------------
    |
    | Uploads are resized and stored as WebP so a large catalog (~100k images)
    | stays fast to deliver. Legal / ID scans use the "document" preset and
    | are stored unchanged. About-page photos use the "about" preset and keep
    | the original file so phone colour profiles are not stripped by GD WebP.
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
        'hero' => [
            // Homepage banner: keep full 3:1 sharpness on desktop / retina (up to 2× 1152×384).
            'max_width' => 2304,
            'max_height' => 768,
            'quality' => 86,
            'convert_webp' => true,
        ],
        'avatar' => [
            'max_width' => 512,
            'max_height' => 512,
            'quality' => 80,
            'convert_webp' => true,
        ],
        'about' => [
            'max_width' => null,
            'max_height' => null,
            'quality' => null,
            'convert_webp' => false,
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
        'brands/originals' => 'catalog',
        'products' => 'catalog',
        'products/gallery' => 'gallery',
        'products/originals' => 'catalog',
        'products/gallery/originals' => 'gallery',
        'store' => 'logo',
        'store/hero' => 'hero',
        'store/blog' => 'catalog',
        'store/about' => 'about',
        'store/watermarks' => 'logo',
        'customers/photos' => 'avatar',
        'suppliers/profile-photos' => 'avatar',
        'users/profile-photos' => 'avatar',
    ],

    /*
    |--------------------------------------------------------------------------
    | Product image watermarks
    |--------------------------------------------------------------------------
    |
    | Clean originals live on the private "local" disk. Storefront only serves
    | watermarked copies from the public disk.
    |
    */

    'product_watermark' => [
        'private_disk' => 'local',
        'public_disk' => 'public',
        'positions' => [
            'bottom-right',
            'bottom-left',
            'top-right',
            'top-left',
            'center',
        ],
        'default_position' => 'bottom-right',
        'default_size_percent' => 15,
        'default_opacity' => 55,
        'marketplace_platforms' => [
            'daraz',
            'amazon',
            'alibaba',
            'facebook',
            'whatsapp',
        ],
        'default_marketplace_preferences' => [
            'daraz' => 'original',
            'amazon' => 'original',
            'alibaba' => 'original',
            'facebook' => 'watermarked',
            'whatsapp' => 'original',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Brand logo watermarks
    |--------------------------------------------------------------------------
    |
    | Brand logos Agricart creates are protected the same way as product photos:
    | private clean originals + public watermarked copies for the storefront.
    |
    */

    'brand_watermark' => [
        'private_disk' => 'local',
        'public_disk' => 'public',
        'positions' => [
            'bottom-right',
            'bottom-left',
            'top-right',
            'top-left',
            'center',
        ],
        'default_position' => 'bottom-right',
        'default_size_percent' => 18,
        'default_opacity' => 70,
        'default_text' => 'Available at Agricart.pk',
    ],

];
