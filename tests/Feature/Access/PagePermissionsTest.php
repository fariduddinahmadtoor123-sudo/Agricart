<?php

namespace Tests\Feature\Access;

use App\Filament\Clusters\Catalog\BrandsPage;
use App\Filament\Clusters\Catalog\ProductsPage;
use App\Filament\Clusters\Contacts\CustomersPage;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Services\Access\ModulePageAccess;
use App\Services\Access\PermissionRegistry;
use Database\Seeders\RolesAndPermissionsSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PagePermissionsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_registry_defines_page_keys_for_all_chrome_modules(): void
    {
        $keys = collect(PermissionRegistry::flatPermissions())->pluck('key')->all();

        $this->assertContains('catalog.products.view', $keys);
        $this->assertContains('contacts.customers.create', $keys);
        $this->assertContains('sales.pos-sales.print', $keys);
        $this->assertContains('settings.users.edit', $keys);
        $this->assertContains('staff.attendance.view', $keys);
        $this->assertContains('staff.view_self', $keys);
        $this->assertContains('logistics.delivered.view', $keys);
        $this->assertContains('fx-learn.economic-radar.view', $keys);
        $this->assertContains('fx-learn.trade-terminal.create', $keys);
        $this->assertContains('fx-learn.trade-terminal.edit', $keys);
        $this->assertContains('fx-learn.academy-journal.view', $keys);
        $this->assertNotContains('catalog.view', $keys);
        $this->assertNotContains('settings.users.manage', $keys);
        $this->assertTrue(PermissionRegistry::moduleUsesPagePermissions('catalog'));
        $this->assertTrue(PermissionRegistry::moduleUsesPagePermissions('fx-learn'));
        $this->assertFalse(PermissionRegistry::moduleUsesPagePermissions('comms'));
    }

    public function test_groups_for_form_exposes_resources_for_chrome_modules(): void
    {
        $form = PermissionRegistry::groupsForForm();

        $this->assertArrayHasKey('resources', $form['catalog']);
        $this->assertArrayHasKey('resources', $form['sales']);
        $this->assertArrayHasKey('resources', $form['fx-learn']);
        $this->assertContains('economic-radar', collect($form['fx-learn']['resources'])->pluck('slug')->all());
        $this->assertContains('trade-terminal', collect($form['fx-learn']['resources'])->pluck('slug')->all());
        $this->assertArrayHasKey('extras', $form['staff']);
        $this->assertContains('products', collect($form['catalog']['resources'])->pluck('slug')->all());
        $this->assertContains('store.messages.view', collect(PermissionRegistry::flatPermissions())->pluck('key')->all());
        $this->assertSame(
            'Agricart Inbox',
            collect(PermissionRegistry::groupsForForm()['store']['resources'])->firstWhere('slug', 'messages')['label_en'] ?? null,
        );
        $this->assertSame(
            'messages',
            collect(PermissionRegistry::groupsForForm()['store']['resources'])->pluck('slug')->get(1),
        );
    }

    public function test_products_only_role_cannot_open_brands(): void
    {
        $role = $this->makeRoleWithKeys([
            'catalog.products.view',
            'catalog.products.edit',
        ]);

        $user = User::factory()->create([
            'role_id' => $role->id,
            'status' => User::STATUS_ACTIVE,
        ]);

        $this->actingAs($user);
        $this->assertTrue(ProductsPage::canAccess());
        $this->assertFalse(BrandsPage::canAccess());
        $this->assertFalse(CustomersPage::canAccess());
    }

    public function test_legacy_catalog_view_expands_then_strips(): void
    {
        $legacy = Permission::query()->create([
            'key' => 'catalog.view',
            'group_key' => 'catalog',
            'label_en' => 'Legacy View Catalog',
            'label_ur' => 'لیگیسی',
            'sort_order' => 9997,
        ]);

        $role = Role::query()->create([
            'name' => 'Legacy Catalog',
            'slug' => 'legacy-catalog-'.uniqid(),
            'is_system' => false,
        ]);
        $role->permissions()->sync([$legacy->id]);

        PermissionRegistry::syncToDatabase();

        $keys = $role->fresh()->permissions()->pluck('key')->all();

        $this->assertNotContains('catalog.view', $keys);
        $this->assertContains('catalog.products.view', $keys);
        $this->assertContains('catalog.brands.view', $keys);

        $user = User::factory()->create([
            'role_id' => $role->id,
            'status' => User::STATUS_ACTIVE,
        ]);

        $this->assertTrue(ModulePageAccess::can($user, 'catalog', 'products', 'view'));
    }

    public function test_legacy_settings_users_manage_expands_to_page_keys(): void
    {
        $legacy = Permission::query()->create([
            'key' => 'settings.users.manage',
            'group_key' => 'settings',
            'label_en' => 'Legacy Manage Users',
            'label_ur' => 'لیگیسی',
            'sort_order' => 9996,
        ]);

        $role = Role::query()->create([
            'name' => 'Legacy Users',
            'slug' => 'legacy-users-'.uniqid(),
            'is_system' => false,
        ]);
        $role->permissions()->sync([$legacy->id]);

        PermissionRegistry::syncToDatabase();

        $keys = $role->fresh()->permissions()->pluck('key')->all();

        $this->assertNotContains('settings.users.manage', $keys);
        $this->assertContains('settings.users.view', $keys);
        $this->assertContains('settings.users.edit', $keys);
    }

    public function test_super_admin_permissions_are_synced_on_registry_sync(): void
    {
        $super = Role::query()->where('slug', 'super-admin')->firstOrFail();
        $super->permissions()->detach();

        $this->assertSame(0, $super->fresh()->permissions()->count());

        $ids = PermissionRegistry::syncToDatabase();

        $this->assertNotEmpty($ids);
        $this->assertSame(count($ids), $super->fresh()->permissions()->count());
    }

    public function test_page_scoped_quotations_clerk_can_open_online_order_form_panel(): void
    {
        $role = $this->makeRoleWithKeys([
            'sales.online-quotations.view',
            'sales.online-quotations.edit',
        ]);

        $user = User::factory()->create([
            'role_id' => $role->id,
            'status' => User::STATUS_ACTIVE,
        ]);

        $this->assertSame(
            'online-sale-orders.form',
            \App\Filament\Layout\FormPanelRegistry::component('online-orders', $user, 'edit'),
        );
        $this->assertTrue(\App\Services\Sales\OnlineSaleOrderPaymentAccess::canViewProof($user));
        $this->assertTrue(\App\Services\Sales\OnlineSaleOrderPaymentAccess::canVerifyProof($user));
    }

    public function test_agricart_inbox_requires_store_messages_view(): void
    {
        $role = $this->makeRoleWithKeys([
            'store.messages.view',
            'store.messages.edit',
        ]);

        $user = User::factory()->create([
            'role_id' => $role->id,
            'status' => User::STATUS_ACTIVE,
        ]);

        $this->actingAs($user);
        $this->assertTrue(\App\Filament\Clusters\Store\MessagesPage::canAccess());
        $this->assertFalse(\App\Filament\Clusters\Store\ReviewsPage::canAccess());
    }

    public function test_store_layout_role_cannot_open_agricart_inbox(): void
    {
        $role = $this->makeRoleWithKeys([
            'store.menu.view',
            'store.menu.edit',
        ]);

        $user = User::factory()->create([
            'role_id' => $role->id,
            'status' => User::STATUS_ACTIVE,
        ]);

        $this->actingAs($user);
        $this->assertFalse(\App\Filament\Clusters\Store\MessagesPage::canAccess());
    }

    /**
     * @param  list<string>  $keys
     */
    private function makeRoleWithKeys(array $keys): Role
    {
        $role = Role::query()->create([
            'name' => 'Page Clerk '.uniqid(),
            'slug' => 'page-clerk-'.uniqid(),
            'is_system' => false,
        ]);

        $ids = Permission::query()->whereIn('key', $keys)->pluck('id')->all();
        $role->permissions()->sync($ids);

        return $role;
    }
}
