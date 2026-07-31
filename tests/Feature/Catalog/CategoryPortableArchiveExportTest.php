<?php

namespace Tests\Feature\Catalog;

use App\Livewire\Categories\ListCategories;
use App\Models\Category;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CategoryPortableArchiveExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_toolbar_export_archive_action_returns_downloadable_ags(): void
    {
        $role = Role::query()->create([
            'name' => 'Super Admin',
            'slug' => 'super-admin',
            'is_system' => true,
        ]);
        $this->actingAs(User::factory()->create([
            'role_id' => $role->id,
            'status' => User::STATUS_ACTIVE,
        ]));

        Category::query()->create([
            'code' => 'CAT-EXPORT',
            'slug' => 'export-cat',
            'status' => 'active',
            'name_en' => 'Export Cat',
            'name_ur' => 'ایکسپورٹ',
            'ai_status' => 'none',
        ]);

        Livewire::test(ListCategories::class)
            ->call('handleToolbarAction', 'export_archive')
            ->assertFileDownloaded();
    }
}
