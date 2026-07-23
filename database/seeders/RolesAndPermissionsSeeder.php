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
        $permissionIds = PermissionRegistry::syncToDatabase();

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
