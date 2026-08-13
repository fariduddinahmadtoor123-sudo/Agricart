<?php

namespace Tests\Feature\Access;

use App\Filament\Clusters\Purchasing\PriceUpdatePage;
use App\Filament\Clusters\Purchasing\PurchasesPage;
use App\Filament\Clusters\PurchasingCluster;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Services\Access\ModulePageAccess;
use App\Services\Access\PermissionRegistry;
use Database\Seeders\RolesAndPermissionsSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PurchasingPagePermissionsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_registry_defines_page_scoped_purchasing_keys(): void
    {
        $keys = collect(PermissionRegistry::flatPermissions())->pluck('key')->all();

        $this->assertContains('purchasing.price-update.view', $keys);
        $this->assertContains('purchasing.price-update.edit', $keys);
        $this->assertContains('purchasing.price-update.print', $keys);
        $this->assertContains('purchasing.purchases.view', $keys);
        $this->assertNotContains('purchasing.view', $keys);
        $this->assertNotContains('purchasing.edit', $keys);
    }

    public function test_price_update_only_role_cannot_open_purchases(): void
    {
        $role = $this->makeRoleWithKeys([
            'purchasing.price-update.view',
            'purchasing.price-update.edit',
        ]);

        $user = User::factory()->create([
            'role_id' => $role->id,
            'status' => User::STATUS_ACTIVE,
        ]);

        $this->actingAs($user);

        $this->assertTrue(PurchasingCluster::canAccess());
        $this->assertTrue(PriceUpdatePage::canAccess());
        $this->assertFalse(PurchasesPage::canAccess());
        $this->assertTrue(ModulePageAccess::can($user, 'purchasing', 'price-update', 'edit'));
        $this->assertFalse(ModulePageAccess::can($user, 'purchasing', 'purchases', 'view'));
    }

    public function test_print_requires_print_permission(): void
    {
        $role = $this->makeRoleWithKeys([
            'purchasing.price-update.view',
            'purchasing.price-update.edit',
        ]);

        $user = User::factory()->create([
            'role_id' => $role->id,
            'status' => User::STATUS_ACTIVE,
        ]);

        $this->assertFalse(ModulePageAccess::can($user, 'purchasing', 'price-update', 'print'));

        $printId = Permission::query()->where('key', 'purchasing.price-update.print')->value('id');
        $role->permissions()->attach($printId);

        $user->unsetRelation('role');
        $user->load('role.permissions');

        $this->assertTrue(ModulePageAccess::can($user->fresh(['role.permissions']), 'purchasing', 'price-update', 'print'));
    }

    public function test_legacy_purchasing_view_expands_to_page_keys_on_sync(): void
    {
        $legacyView = Permission::query()->create([
            'key' => 'purchasing.view',
            'group_key' => 'purchasing',
            'label_en' => 'Legacy View Purchasing',
            'label_ur' => 'لیگیسی',
            'sort_order' => 9999,
        ]);

        $role = Role::query()->create([
            'name' => 'Legacy Purchaser',
            'slug' => 'legacy-purchaser',
            'is_system' => false,
        ]);
        $role->permissions()->sync([$legacyView->id]);

        PermissionRegistry::syncToDatabase();

        $keys = $role->fresh()->permissions()->pluck('key')->all();

        $this->assertNotContains('purchasing.view', $keys);
        $this->assertContains('purchasing.price-update.view', $keys);
        $this->assertContains('purchasing.purchases.view', $keys);
        $this->assertContains('purchasing.price-update.print', $keys);

        $user = User::factory()->create([
            'role_id' => $role->id,
            'status' => User::STATUS_ACTIVE,
        ]);

        $this->assertTrue(ModulePageAccess::can($user, 'purchasing', 'purchases', 'view'));
        $this->assertTrue(ModulePageAccess::userHasKey($user, 'purchasing.price-update.view'));
    }

    public function test_saving_page_permissions_does_not_keep_legacy_module_keys(): void
    {
        $legacyView = Permission::query()->create([
            'key' => 'purchasing.view',
            'group_key' => 'purchasing',
            'label_en' => 'Legacy View Purchasing',
            'label_ur' => 'لیگیسی',
            'sort_order' => 9998,
        ]);

        $role = Role::query()->create([
            'name' => 'Price Only Role',
            'slug' => 'price-only-role',
            'is_system' => false,
        ]);
        $role->permissions()->sync([$legacyView->id]);

        $admin = User::factory()->create([
            'role_id' => Role::query()->where('slug', 'super-admin')->value('id'),
            'status' => User::STATUS_ACTIVE,
        ]);

        $this->actingAs($admin);

        Livewire::test(\App\Livewire\Roles\Form::class)
            ->call('openForm', $role->id, 'edit')
            ->set('selectedPermissions', [
                'purchasing.overview.view',
                'purchasing.price-update.view',
                'purchasing.price-update.edit',
                'purchasing.price-update.print',
                'purchasing.view', // stuck legacy key must be stripped on save
            ])
            ->call('saveAndClose');

        $keys = $role->fresh()->permissions()->pluck('key')->all();

        $this->assertNotContains('purchasing.view', $keys);
        $this->assertContains('purchasing.overview.view', $keys);
        $this->assertContains('purchasing.price-update.view', $keys);
        $this->assertContains('purchasing.price-update.edit', $keys);
        $this->assertNotContains('purchasing.purchases.view', $keys);

        $clerk = User::factory()->create([
            'role_id' => $role->id,
            'status' => User::STATUS_ACTIVE,
        ]);

        $this->actingAs($clerk);
        $this->assertTrue(PriceUpdatePage::canAccess());
        $this->assertFalse(PurchasesPage::canAccess());
    }

    public function test_sync_strips_stuck_legacy_without_flooding_existing_page_keys(): void
    {
        $legacyView = Permission::query()->create([
            'key' => 'purchasing.view',
            'group_key' => 'purchasing',
            'label_en' => 'Legacy View Purchasing',
            'label_ur' => 'لیگیسی',
            'sort_order' => 9998,
        ]);

        $role = $this->makeRoleWithKeys([
            'purchasing.overview.view',
            'purchasing.price-update.view',
            'purchasing.price-update.edit',
            'purchasing.price-update.print',
        ]);
        $role->permissions()->attach($legacyView->id);

        PermissionRegistry::syncToDatabase();

        $keys = $role->fresh()->permissions()->pluck('key')->all();

        $this->assertNotContains('purchasing.view', $keys);
        $this->assertContains('purchasing.price-update.view', $keys);
        $this->assertNotContains('purchasing.purchases.view', $keys);

        $user = User::factory()->create([
            'role_id' => $role->id,
            'status' => User::STATUS_ACTIVE,
        ]);

        $this->actingAs($user);
        $this->assertTrue(PriceUpdatePage::canAccess());
        $this->assertFalse(PurchasesPage::canAccess());
    }

    public function test_role_form_shows_purchasing_page_permission_rows(): void
    {
        $this->actingAs(User::factory()->create([
            'role_id' => Role::query()->where('slug', 'super-admin')->value('id'),
            'status' => User::STATUS_ACTIVE,
        ]));

        Livewire::test(\App\Livewire\Roles\Form::class)
            ->assertSee(__('agricart.roles.hints.page_permissions'), false)
            ->assertSee('purchasing.price-update.view', false)
            ->assertSee('agricart-role-form__resource', false);
    }

    /**
     * @param  list<string>  $keys
     */
    private function makeRoleWithKeys(array $keys): Role
    {
        $role = Role::query()->create([
            'name' => 'Price Clerk '.uniqid(),
            'slug' => 'price-clerk-'.uniqid(),
            'is_system' => false,
        ]);

        $ids = Permission::query()->whereIn('key', $keys)->pluck('id')->all();
        $role->permissions()->sync($ids);

        return $role;
    }
}
