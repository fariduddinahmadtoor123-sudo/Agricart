<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use App\Services\Access\PermissionRegistry;
use Illuminate\Database\Seeder;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $permissionIds = [];

        foreach (PermissionRegistry::flatPermissions() as $definition) {
            $permission = Permission::query()->updateOrCreate(
                ['key' => $definition['key']],
                [
                    'group_key' => $definition['group_key'],
                    'label_en' => $definition['label_en'],
                    'label_ur' => $definition['label_ur'],
                    'sort_order' => $definition['sort_order'],
                ],
            );

            $permissionIds[] = $permission->id;
        }

        $superAdmin = Role::query()->updateOrCreate(
            ['slug' => 'super-admin'],
            [
                'name' => 'Super Admin',
                'description' => 'Full system access. This role cannot be deleted.',
                'is_system' => true,
            ],
        );

        $superAdmin->permissions()->sync($permissionIds);
    }
}
