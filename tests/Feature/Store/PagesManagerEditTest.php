<?php

namespace Tests\Feature\Store;

use App\Livewire\Store\PagesManager;
use App\Models\Role;
use App\Models\StorePage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PagesManagerEditTest extends TestCase
{
    use RefreshDatabase;

    public function test_edit_page_loads_fields_and_save_updates_existing_row(): void
    {
        $admin = $this->superAdmin();

        $page = StorePage::query()->create([
            'title_en' => 'Privacy Policy',
            'title_ur' => 'پرائیویسی',
            'slug' => 'privacy-policy',
            'content_en' => 'Original privacy text.',
            'content_ur' => null,
            'is_active' => true,
            'show_in_menu' => false,
            'show_in_footer' => false,
            'show_in_legal' => true,
            'show_in_quick_links' => false,
            'show_in_registration' => false,
            'sort_order' => 1,
        ]);

        Livewire::actingAs($admin)
            ->test(PagesManager::class)
            ->call('editPage', $page->id)
            ->assertSet('editingId', $page->id)
            ->assertSet('title_en', 'Privacy Policy')
            ->assertSet('slug', 'privacy-policy')
            ->assertSet('content_en', 'Original privacy text.')
            ->assertSet('show_in_legal', true)
            ->set('title_en', 'Privacy Policy Updated')
            ->set('content_en', 'Updated privacy body.')
            ->call('savePage')
            ->assertHasNoErrors()
            ->assertSet('editingId', null);

        $this->assertDatabaseHas('store_pages', [
            'id' => $page->id,
            'slug' => 'privacy-policy',
            'title_en' => 'Privacy Policy Updated',
            'content_en' => 'Updated privacy body.',
        ]);

        $this->assertSame(1, StorePage::query()->count());
    }

    private function superAdmin(): User
    {
        $role = Role::query()->firstOrCreate(
            ['slug' => 'super-admin'],
            ['name' => 'Super Admin', 'is_system' => true],
        );

        return User::factory()->create([
            'role_id' => $role->id,
            'status' => User::STATUS_ACTIVE,
        ]);
    }
}
