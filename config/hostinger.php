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
