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
        $this->assertContains(__('agricart.pages.purchasing.purchase-planning'), $purchasing);
        $this->assertContains(__('agricart.pages.marketplace.price-control'), $marketplace);
        $this->assertContains(__('agricart.pages.marketplace.quantity-control'), $marketplace);
        $this->assertContains(__('agricart.pages.marketplace.secret-coding'), $marketplace);
        $this->assertContains(__('agricart.pages.contacts.transport-companies'), $contacts);
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
            ->assertSee(__('agricart.roles.hints.pages'), false)
            ->assertSee(__('agricart.pages.marketplace.price-control'), false)
            ->assertSee(__('agricart.pages.purchasing.price-update'), false)
            ->assertSee(__('agricart.pages.purchasing.purchase-planning'), false)
            ->assertSee(__('agricart.pages.contacts.transport-companies'), false);
    }
}
