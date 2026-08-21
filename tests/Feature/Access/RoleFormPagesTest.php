<?php

namespace Tests\Feature\Access;

use App\Livewire\Roles\Form;
use App\Models\Role;
use App\Models\User;
use App\Services\Access\PermissionRegistry;
use Database\Seeders\RolesAndPermissionsSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class RoleFormPagesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    public function test_permission_registry_lists_current_module_pages(): void
    {
        $purchasing = PermissionRegistry::pagesForModule('purchasing');
        $marketplace = PermissionRegistry::pagesForModule('marketplace');
        $contacts = PermissionRegistry::pagesForModule('contacts');

        $this->assertContains(__('agricart.pages.purchasing.price-update'), $purchasing);
        $this->assertContains(__('agricart.pages.purchasing.price-tags'), $purchasing);
        $this->assertContains(__('agricart.pages.purchasing.rate-list'), $purchasing);
        $this->assertContains(__('agricart.pages.purchasing.purchase-planning'), $purchasing);
        $this->assertContains(__('agricart.pages.marketplace.price-control'), $marketplace);
        $this->assertContains(__('agricart.pages.marketplace.quantity-control'), $marketplace);
        $this->assertContains(__('agricart.pages.marketplace.secret-coding'), $marketplace);
        $this->assertContains(__('agricart.pages.contacts.transport-companies'), $contacts);

        $form = PermissionRegistry::groupsForForm()['purchasing'];
        $this->assertArrayHasKey('resources', $form);
        $slugs = collect($form['resources'])->pluck('slug')->all();
        $this->assertContains('price-update', $slugs);
        $this->assertContains('purchases', $slugs);

        $catalogForm = PermissionRegistry::groupsForForm()['catalog'];
        $this->assertArrayHasKey('resources', $catalogForm);
        $this->assertContains('products', collect($catalogForm['resources'])->pluck('slug')->all());
    }

    public function test_role_form_shows_module_pages_and_full_width_description(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->actingAs(User::factory()->create([
            'role_id' => Role::query()->where('slug', 'super-admin')->value('id'),
            'status' => User::STATUS_ACTIVE,
        ]));

        Livewire::test(Form::class)
            ->assertSee('agricart-role-form__meta', false)
            ->assertSee('agricart-role-form__description', false)
            ->assertDontSee('agricart-user-form__identity-grid', false)
            ->assertSee(__('agricart.roles.hints.page_permissions'), false)
            ->assertSee('Price Control', false)
            ->assertSee('Hero Slider', false)
            ->assertSee(__('agricart.roles.actions.check_all'), false)
            ->assertSee(__('agricart.roles.actions.uncheck_all'), false)
            ->assertSee('agricart-role-form__resource', false)
            ->assertSee('Transport / Goods', false)
            ->assertSee('Agricart Inbox', false)
            ->assertSee(__('agricart.roles.hints.search_permissions_placeholder'), false);
    }

    public function test_role_form_check_all_selects_every_permission(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->actingAs(User::factory()->create([
            'role_id' => Role::query()->where('slug', 'super-admin')->value('id'),
            'status' => User::STATUS_ACTIVE,
        ]));

        $allKeys = collect(PermissionRegistry::flatPermissions())->pluck('key')->unique()->values()->all();

        $component = Livewire::test(Form::class)
            ->call('openForm')
            ->call('selectAllPermissions');

        $this->assertEqualsCanonicalizing($allKeys, $component->get('selectedPermissions'));

        Livewire::test(Form::class)
            ->call('openForm')
            ->set('selectedPermissions', ['store.hero-slider.view'])
            ->call('deselectAllPermissions')
            ->assertSet('selectedPermissions', []);
    }

    public function test_permission_registry_includes_store_hero_slider(): void
    {
        $keys = collect(PermissionRegistry::flatPermissions())->pluck('key')->all();

        $this->assertContains('store.hero-slider.view', $keys);
        $this->assertContains('store.hero-slider.edit', $keys);

        $store = PermissionRegistry::groupsForForm()['store'];
        $this->assertContains('hero-slider', collect($store['resources'])->pluck('slug')->all());
    }
}