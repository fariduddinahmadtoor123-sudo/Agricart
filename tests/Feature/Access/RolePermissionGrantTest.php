<?php

namespace Tests\Feature\Access;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Services\Access\RoleService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class RolePermissionGrantTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_non_super_admin_with_roles_manage_cannot_sync_permissions(): void
    {
        $manager = $this->userWithRolesManagePermission();
        $target = Role::query()->create([
            'name' => 'Custom '.uniqid(),
            'slug' => 'custom-'.uniqid(),
            'is_system' => false,
        ]);

        $service = app(RoleService::class);

        $this->expectException(ValidationException::class);

        try {
            $service->update($target, [
                'name' => $target->name,
                'description' => null,
            ], ['catalog.products.view'], $manager);
        } catch (ValidationException $exception) {
            $this->assertSame(
                __('agricart.roles.messages.permissions_super_admin_only'),
                collect($exception->errors())->flatten()->first(),
            );

            throw $exception;
        }
    }

    public function test_super_admin_can_sync_role_permissions(): void
    {
        $super = $this->makeSuperAdmin('super-perms@test.test');
        $target = Role::query()->create([
            'name' => 'Editable '.uniqid(),
            'slug' => 'editable-'.uniqid(),
            'is_system' => false,
        ]);

        $updated = app(RoleService::class)->update($target, [
            'name' => $target->name,
            'description' => 'Updated',
        ], ['catalog.products.view', 'catalog.products.create'], $super);

        $this->assertSame(
            ['catalog.products.create', 'catalog.products.view'],
            $updated->permissions->pluck('key')->sort()->values()->all(),
        );
    }

    private function makeSuperAdmin(string $email): User
    {
        return User::factory()->create([
            'role_id' => Role::query()->where('slug', 'super-admin')->value('id'),
            'status' => User::STATUS_ACTIVE,
            'email' => $email,
        ]);
    }

    private function userWithRolesManagePermission(): User
    {
        $role = Role::query()->create([
            'name' => 'Role Manager '.uniqid(),
            'slug' => 'role-manager-'.uniqid(),
            'is_system' => false,
        ]);

        $permissionId = Permission::query()
            ->where('key', 'settings.permission.edit')
            ->value('id');

        $role->permissions()->sync([$permissionId]);

        return User::factory()->create([
            'role_id' => $role->id,
            'status' => User::STATUS_ACTIVE,
            'email' => 'role-manager@test.test',
        ]);
    }
}
