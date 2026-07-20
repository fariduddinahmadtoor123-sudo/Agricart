<?php

use App\Models\Brand;
use App\Models\CatalogAttribute;
use App\Models\CatalogControl;
use App\Models\Category;
use App\Models\CompanyProfile;
use App\Models\Customer;
use App\Models\PackingUnit;
use App\Models\PrintProfile;
use App\Models\Product;
use App\Models\Role;
use App\Models\StoreSetting;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\User;

return [
    /*
    |--------------------------------------------------------------------------
    | Auditable models (create / update / delete)
    |--------------------------------------------------------------------------
    |
    | Only these Eloquent models are recorded when an admin (web guard) mutates them.
    |
    */
    'models' => [
        User::class,
        Role::class,
        Product::class,
        Category::class,
        Brand::class,
        Unit::class,
        PackingUnit::class,
        CatalogAttribute::class,
        CatalogControl::class,
        Supplier::class,
        Customer::class,
        CompanyProfile::class,
        StoreSetting::class,
        PrintProfile::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Attribute keys to redact from properties payloads
    |--------------------------------------------------------------------------
    */
    'redact_keys' => [
        'password',
        'password_confirmation',
        'current_password',
        'remember_token',
        'api_key',
        'api_token',
        'token',
        'secret',
        'access_token',
        'refresh_token',
        'private_key',
        'client_secret',
    ],
];
