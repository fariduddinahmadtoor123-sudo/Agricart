<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Agricart Communication Platform
    |--------------------------------------------------------------------------
    |
    | Generic core for all future communication channels. Add new actor types,
    | linkable records, and MIME groups here — do not hard-code channel lists
    | in application services.
    |
    */

    'engine_enabled' => (bool) env('COMMUNICATION_ENGINE_ENABLED', true),

    'attachment_disk' => env('COMMUNICATION_ATTACHMENT_DISK', 'local'),

    'attachment_root' => 'communication/attachments',

    'max_attachment_bytes' => (int) env('COMMUNICATION_MAX_ATTACHMENT_BYTES', 10_485_760),

    /*
    | Actor type keys stored on participants. Map to Eloquent models.
    | Future: vendor, dealer, driver, ai_assistant — register only.
    */
    'actor_types' => [
        'user' => App\Models\User::class,
        'customer' => App\Models\Customer::class,
        'supplier' => App\Models\Supplier::class,
        'system' => null,
    ],

    /*
    | Linkable record keys for conversation_links. Future modules: add a key.
    */
    'linkable_types' => [
        'online_sale_order' => App\Models\OnlineSaleOrder::class,
        'product' => App\Models\Product::class,
        'product_review' => App\Models\ProductReview::class,
        'brand' => App\Models\Brand::class,
        'category' => App\Models\Category::class,
        'supplier' => App\Models\Supplier::class,
        'customer' => App\Models\Customer::class,
        'user' => App\Models\User::class,
    ],

    /*
    | Permission required for a User to attach/view a linked record.
    | Customers/suppliers rely on participation + channel policy instead.
    */
    'linkable_permissions' => [
        'online_sale_order' => 'sales.view',
        'product' => 'catalog.view',
        'product_review' => 'store.view',
        'brand' => 'catalog.view',
        'category' => 'catalog.view',
        'supplier' => ['purchasing.view', 'contacts.view'],
        'customer' => 'contacts.view',
        'user' => 'staff.view',
    ],

    /*
    | Record-bound channel threads (Supplier / Brand / future partners).
    | Services resolve by profile key — do not hard-code channel lists in UI.
    */
    'record_threads' => [
        'supplier' => [
            'channel_key' => 'supplier',
            'linkable_type' => 'supplier',
            'include_linked_actor' => true,
            'linked_actor_type' => 'supplier',
            'conversation_type' => 'direct',
            'purpose' => 'supplier_thread',
        ],
        'brand' => [
            'channel_key' => 'brand',
            'linkable_type' => 'brand',
            'include_linked_actor' => false,
            'linked_actor_type' => null,
            'conversation_type' => 'group',
            'purpose' => 'brand_thread',
        ],
    ],

    'mime_groups' => [
        'image' => [
            'image/jpeg',
            'image/png',
            'image/webp',
            'image/gif',
        ],
        'document' => [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'text/plain',
            'text/csv',
        ],
        'archive' => [
            'application/zip',
            'application/x-zip-compressed',
        ],
        'audio' => [
            'audio/mpeg',
            'audio/mp4',
            'audio/ogg',
            'audio/webm',
            'audio/wav',
        ],
    ],

    /*
    | Default channel definitions used by the seeder. Runtime source of truth
    | is the comm_channels table — seeder only upserts these keys.
    */
    'default_channels' => [
        'customer_support' => [
            'name' => 'Customer Support',
            'allowed_actor_types' => ['user', 'customer', 'system'],
            'allows_groups' => false,
            'allows_attachments' => true,
            'allowed_mime_groups' => ['image', 'document'],
            'allows_tasks' => true,
            'visibility' => 'participants',
            'settings' => [
                'permission' => 'comms.channel.customer_support.access',
                'fallback_permissions' => ['store.view', 'store.edit'],
            ],
            'sort_order' => 10,
        ],
        'staff_ops' => [
            'name' => 'Staff Operations',
            'allowed_actor_types' => ['user', 'system'],
            'allows_groups' => true,
            'allows_attachments' => true,
            'allowed_mime_groups' => ['image', 'document', 'archive'],
            'allows_tasks' => true,
            'visibility' => 'participants',
            'settings' => [
                'permission' => 'comms.channel.staff_ops.access',
                'fallback_permissions' => ['sales.view', 'staff.view'],
                'allow_any_admin' => true,
            ],
            'sort_order' => 20,
        ],
        'supplier' => [
            'name' => 'Supplier Communication',
            'allowed_actor_types' => ['user', 'supplier', 'system'],
            'allows_groups' => true,
            'allows_attachments' => true,
            'allowed_mime_groups' => ['image', 'document', 'archive'],
            'allows_tasks' => true,
            'visibility' => 'participants',
            'settings' => [
                'permission' => 'comms.channel.supplier.access',
                'fallback_permissions' => ['purchasing.view', 'contacts.view'],
            ],
            'sort_order' => 30,
            'is_active' => true,
        ],
        'brand' => [
            'name' => 'Brand Communication',
            'allowed_actor_types' => ['user', 'system'],
            'allows_groups' => true,
            'allows_attachments' => true,
            'allowed_mime_groups' => ['image', 'document'],
            'allows_tasks' => false,
            'visibility' => 'participants',
            'settings' => [
                'permission' => 'comms.channel.brand.access',
                'fallback_permissions' => ['catalog.view'],
            ],
            'sort_order' => 40,
        ],
        'internal_notes' => [
            'name' => 'Internal Notes',
            'allowed_actor_types' => ['user', 'system'],
            'allows_groups' => true,
            'allows_attachments' => true,
            'allowed_mime_groups' => ['image', 'document', 'archive'],
            'allows_tasks' => true,
            'visibility' => 'record_acl',
            'settings' => [
                'permission' => 'comms.channel.internal_notes.access',
                'fallback_permissions' => [
                    'sales.view',
                    'catalog.view',
                    'store.view',
                    'purchasing.view',
                    'logistics.view',
                    'contacts.view',
                ],
            ],
            'sort_order' => 50,
        ],
        'system' => [
            'name' => 'System Notifications',
            'allowed_actor_types' => ['user', 'customer', 'supplier', 'system'],
            'allows_groups' => false,
            'allows_attachments' => false,
            'allowed_mime_groups' => [],
            'allows_tasks' => false,
            'visibility' => 'participants',
            'settings' => [
                'permission' => 'comms.channel.system.access',
            ],
            'sort_order' => 60,
        ],
        'task' => [
            'name' => 'Task Communication',
            'allowed_actor_types' => ['user', 'system'],
            'allows_groups' => true,
            'allows_attachments' => true,
            'allowed_mime_groups' => ['image', 'document', 'archive'],
            'allows_tasks' => true,
            'visibility' => 'participants',
            'settings' => [
                'permission' => 'comms.channel.task.access',
                'fallback_permissions' => [
                    'sales.view',
                    'catalog.view',
                    'store.view',
                    'purchasing.view',
                    'logistics.view',
                    'contacts.view',
                ],
            ],
            'sort_order' => 70,
        ],
    ],
];
