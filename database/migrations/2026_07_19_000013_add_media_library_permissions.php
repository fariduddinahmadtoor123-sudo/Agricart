<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        foreach ([
            'settings.media.view' => 'View media library',
            'settings.media.manage' => 'Manage media library',
        ] as $key => $label) {
            DB::table('permissions')->updateOrInsert(
                ['key' => $key],
                [
                    'group_key' => 'settings',
                    'label_en' => $label,
                    'label_ur' => null,
                    'sort_order' => 1010,
                    'updated_at' => now(),
                    'created_at' => now(),
                ],
            );
        }
    }

    public function down(): void
    {
        DB::table('permissions')
            ->whereIn('key', ['settings.media.view', 'settings.media.manage'])
            ->delete();
    }
};
