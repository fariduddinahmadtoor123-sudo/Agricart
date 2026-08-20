<?php

namespace Tests\Feature\Access;

use App\Filament\Layout\CatalogPageToolbar;
use App\Filament\Layout\ContactsPageToolbar;
use App\Filament\Layout\MarketplacePageToolbar;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Services\Access\ModulePageAccess;
use App\Services\Access\PermissionRegistry;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModuleCreateButtonPermissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_contacts_suppliers_create_shows_add_button(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $user = $this->userWithPermissions([
            'contacts.suppliers.view',
            'contacts.suppliers.create',
        ]);

        $this->actingAs($user)
            ->get('/admin/contacts/suppliers')
            ->assertOk()
            ->assertSee('data-form-title="'.__('suppliers.actions.add').'"', false);
    }

    public function test_contacts_suppliers_view_only_hides_add_button(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $user = $this->userWithPermissions([
            'contacts.suppliers.view',
        ]);

        $this->actingAs($user)
            ->get('/admin/contacts/suppliers')
            ->assertOk()
            ->assertDontSee('data-form-title="'.__('suppliers.actions.add').'"', false);
    }

    public function test_contacts_suppliers_print_requires_print_permission(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->assertTrue(ModulePageAccess::can(
            $this->userWithPermissions(['contacts.suppliers.view', 'contacts.suppliers.print']),
            'contacts',
            'suppliers',
            'print',
        ));

        $this->assertFalse(ModulePageAccess::can(
            $this->userWithPermissions(['contacts.suppliers.view']),
            'contacts',
            'suppliers',
            'print',
        ));
    }

    public function test_marketplace_price_control_create_is_assignable_and_shows_add(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->assertContains(
            'create',
            PermissionRegistry::modulePages()['marketplace']['price-control']['actions'],
        );

        $user = $this->userWithPermissions([
            'marketplace.price-control.view',
            'marketplace.price-control.create',
        ]);

        $this->actingAs($user)
            ->get('/admin/marketplace/price-control')
            ->assertOk()
            ->assertSee(__('price_controls.actions.add'), false);
    }

    public function test_catalog_product_reviews_create_shows_add_button(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $user = $this->userWithPermissions([
            'catalog.product-reviews.view',
            'catalog.product-reviews.create',
        ]);

        $this->actingAs($user)
            ->get('/admin/catalog/product-reviews')
            ->assertOk()
            ->assertSee(__('agricart.products.review_page.actions.add'), false);
    }

    public function test_roles_form_lists_contacts_suppliers_create_and_store_hero_slider(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $keys = collect(PermissionRegistry::groupsForForm())
            ->flatMap(fn (array $group) => collect($group['permissions'])->pluck('key'))
            ->all();

        $this->assertContains('contacts.suppliers.create', $keys);
        $this->assertContains('contacts.suppliers.print', $keys);
        $this->assertContains('store.hero-slider.view', $keys);
        $this->assertContains('store.hero-slider.edit', $keys);
        $this->assertContains('marketplace.secret-coding.create', $keys);
        $this->assertContains('catalog.product-reviews.create', $keys);
    }

    public function test_toolbar_classes_exist_for_create_gated_modules(): void
    {
        $this->assertTrue(method_exists(ContactsPageToolbar::class, 'config'));
        $this->assertTrue(method_exists(CatalogPageToolbar::class, 'config'));
        $this->assertTrue(method_exists(MarketplacePageToolbar::class, 'config'));
    }

    /**
     * @param  list<string>  $keys
     */
    private function userWithPermissions(array $keys): User
    {
        $role = Role::query()->create([
            'name' => 'Access Test '.uniqid(),
            'slug' => 'access-test-'.uniqid(),
            'is_system' => false,
        ]);

        $permissionIds = Permission::query()
            ->whereIn('key', $keys)
            ->pluck('id')
            ->all();

        $this->assertCount(count($keys), $permissionIds, 'Missing permissions: '.implode(', ', $keys));

        $role->permissions()->sync($permissionIds);
        $role->touch();

        return User::factory()->create([
            'role_id' => $role->id,
            'status' => User::STATUS_ACTIVE,
        ]);
    }
}
