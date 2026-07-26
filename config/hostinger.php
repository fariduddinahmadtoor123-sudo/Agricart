<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Hostinger shared hosting (fixed public_html document root)
    |--------------------------------------------------------------------------
    |
    | Clone/pull the Git repo OUTSIDE public_html. After each deploy, sync
    | public/ assets into public_html with: php artisan hostinger:sync-public
    |
    */

    'public_html' => env('HOSTINGER_PUBLIC_HTML'),

    /*
    | Mirror storage/app/public into the Apache-facing directory after each
    | upload/delete (and after full backup restore via hostinger:sync-storage).
    |
    | Split layout (app outside web root): {HOSTINGER_PUBLIC_HTML}/storage
    | Direct Git checkout inside public_html: {app}/public/storage
    | Never mirrors into Laravel's storage/ root (private files).
    |
    | No-op on Laragon/VPS when public/storage is already a correct symlink.
    */
    'mirror_public_media_on_write' => filter_var(
        env('HOSTINGER_MIRROR_PUBLIC_MEDIA_ON_WRITE', true),
        FILTER_VALIDATE_BOOL,
    ),

    'storage_mirror_exclusions' => array_values(array_filter(array_map(
        trim(...),
        explode(',', env(
            'HOSTINGER_STORAGE_MIRROR_EXCLUSIONS',
            'private,livewire-tmp,users/cnic,online-orders/customer-receipts,online-orders/payment-proofs,online-orders/shipping-proofs',
        ))
    ))),

    /*
    | Sibling folder names tried by public/resolve-laravel-root.php (lowercase match).
    */
    'app_folder_names' => array_filter(array_map(
        trim(...),
        explode(',', env('HOSTINGER_APP_FOLDER_NAMES', 'agricart,Agricart'))
    )),

];
