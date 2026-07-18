<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class FreshStartSeeder extends Seeder
{
    public function run(): void
    {
        Schema::disableForeignKeyConstraints();

        $tables = [
            'product_display_category',
            'product_attribute',
            'product_control',
            'products',
            'category_display',
            'categories',
            'brands',
            'packing_units',
            'units',
            'attributes',
            'controls',
            'permission_role',
            'users',
            'roles',
            'sessions',
            'password_reset_tokens',
        ];

        foreach ($tables as $table) {
            if (Schema::hasTable($table)) {
                DB::table($table)->delete();
            }
        }

        Schema::enableForeignKeyConstraints();

        $this->purgeUploadedCatalogFiles();

        $this->call(RolesAndPermissionsSeeder::class);

        $superAdminRole = Role::query()->where('slug', 'super-admin')->firstOrFail();

        User::query()->create([
            'user_number' => 'USR-00001',
            'name' => 'Super Admin',
            'email' => 'admin@agricart.test',
            'password' => 'password',
            'role_id' => $superAdminRole->id,
            'status' => User::STATUS_ACTIVE,
        ]);

        $this->call(QuantityControlSeeder::class);

        $this->command?->info('Fresh start complete.');
        $this->command?->info('Super Admin: admin@agricart.test / password');
        $this->command?->info('Login: ' . url('/admin/login'));
    }

    private function purgeUploadedCatalogFiles(): void
    {
        foreach (['categories', 'brands', 'products', 'users/profile-photos', 'users/cnic'] as $directory) {
            if (Storage::disk('public')->exists($directory)) {
                Storage::disk('public')->deleteDirectory($directory);
            }
        }
    }
}
