<?php

namespace Tests\Feature\Access;

use App\Filament\Layout\CatalogSubNavigation;
use App\Filament\Layout\ModuleChrome;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Services\Access\PermissionRegistry;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClusterModuleKeyPermissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_module_sub_navigation_exposes_a_registry_module_key(): void
    {
        $registryKeys = array_keys(PermissionRegistry::groups());

        foreach (ModuleChrome::modules() as $module) {
            $subNav = $module['subNavigation'] ?? null;

            if (! is_string($subNav) || ! class_exists($subNav)) {
                continue;
            }

            if (! method_exists($subNav, 'moduleKey')) {
                continue;
            }

            $key = $subNav::moduleKey();

            $this->assertNotSame('', $key, "{$subNav}::moduleKey() must not be empty.");
            $this->assertContains($key, $registryKeys, "{$subNav}::moduleKey() [{$key}] missing from PermissionRegistry.");
        }
    }

    public function test_catalog_module_key_is_catalog(): void
    {
        $this->assertSame('catalog', CatalogSubNavigation::moduleKey());
    }

    public function test_catalog_create_permission_shows_add_product_button(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $role = Role::query()->create([
            'name' => 'Catalog Operator',
            'slug' => 'catalog-operator-test',
            'is_system' => false,
        ]);

        $permissionIds = Permission::query()
            ->whereIn('key', ['catalog.products.view', 'catalog.products.create'])
            ->pluck('id')
            ->all();

        $role->permissions()->sync($permissionIds);
        $role->touch();

        $user = User::factory()->create([
            'role_id' => $role->id,
            'status' => User::STATUS_ACTIVE,
        ]);

        $this->actingAs($user)
            ->get('/admin/catalog/products')
            ->assertOk()
            ->assertSee(__('agricart.toolbar.add', [
                'item' => __('agricart.pages.catalog.products'),
            ]), false);
    }

    public function test_catalog_view_only_hides_add_product_button(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $role = Role::query()->create([
            'name' => 'Catalog Viewer',
            'slug' => 'catalog-viewer-test',
            'is_system' => false,
        ]);

        $permissionIds = Permission::query()
            ->where('key', 'catalog.products.view')
            ->pluck('id')
            ->all();

        $role->permissions()->sync($permissionIds);
        $role->touch();

        $user = User::factory()->create([
            'role_id' => $role->id,
            'status' => User::STATUS_ACTIVE,
        ]);

        $this->actingAs($user)
            ->get('/admin/catalog/products')
            ->assertOk()
            ->assertDontSee(__('agricart.toolbar.add', [
                'item' => __('agricart.pages.catalog.products'),
            ]), false);
    }
}
