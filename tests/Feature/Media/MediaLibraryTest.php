<?php

namespace Tests\Feature\Media;

use App\Filament\Clusters\Settings\MediaLibraryPage;
use App\Livewire\Media\Form;
use App\Livewire\Media\ListMedia;
use App\Models\Category;
use App\Models\MediaAsset;
use App\Models\MediaUsage;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Services\Media\MediaLibraryService;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class MediaLibraryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        Storage::fake('public');
    }

    public function test_upload_preserves_original_name_and_search_finds_it(): void
    {
        $this->actingAs($this->userWithPermissions(['settings.view', 'settings.media.view', 'settings.media.manage']));

        Livewire::test(Form::class)
            ->dispatch('media-form-open')
            ->set('upload', UploadedFile::fake()->image('fertilizer-bag.png', 900, 600))
            ->set('displayName', 'Premium Fertilizer Bag')
            ->call('save')
            ->assertHasNoErrors();

        $asset = MediaAsset::query()->sole();
        $this->assertSame('fertilizer-bag.png', $asset->original_name);
        $this->assertSame('Premium Fertilizer Bag', $asset->display_name);
        $this->assertSame('media-library', $asset->source_module);
        $this->assertStringEndsWith('.webp', $asset->path);
        Storage::disk('public')->assertExists($asset->path);

        Livewire::test(ListMedia::class)
            ->dispatch('media-library-search', query: 'fertilizer-bag')
            ->assertSee('Premium Fertilizer Bag');
    }

    public function test_backfill_is_idempotent_and_excludes_private_documents(): void
    {
        Storage::disk('public')->put('categories/tools.jpg', UploadedFile::fake()->image('tools.jpg')->getContent());
        Storage::disk('public')->put('users/profile-photos/person.jpg', UploadedFile::fake()->image('person.jpg')->getContent());
        Storage::disk('public')->put('users/cnic/front.jpg', UploadedFile::fake()->image('front.jpg')->getContent());

        $category = Category::withoutEvents(fn () => Category::query()->create([
            'slug' => 'tools',
            'name_en' => 'Tools',
            'name_ur' => 'اوزار',
            'image_path' => 'categories/tools.jpg',
        ]));
        User::withoutEvents(fn () => User::factory()->create([
            'profile_photo_path' => 'users/profile-photos/person.jpg',
            'cnic_front_path' => 'users/cnic/front.jpg',
        ]));

        $this->artisan('media:backfill-library')->assertSuccessful();
        $firstAssets = MediaAsset::query()->count();
        $firstUsages = MediaUsage::query()->count();
        $this->artisan('media:backfill-library')->assertSuccessful();

        $this->assertSame($firstAssets, MediaAsset::query()->count());
        $this->assertSame($firstUsages, MediaUsage::query()->count());
        $this->assertDatabaseHas('media_assets', [
            'path' => 'categories/tools.jpg',
            'display_name' => 'Tools — Main image',
        ]);
        $this->assertDatabaseHas('media_usages', [
            'usable_type' => $category->getMorphClass(),
            'usable_id' => $category->id,
        ]);
        $this->assertDatabaseHas('media_assets', ['path' => 'users/profile-photos/person.jpg']);
        $this->assertDatabaseMissing('media_assets', ['path' => 'users/cnic/front.jpg']);
    }

    public function test_shared_usage_blocks_trash_until_every_reference_is_removed(): void
    {
        Storage::disk('public')->put('categories/shared.jpg', UploadedFile::fake()->image('shared.jpg')->getContent());
        $first = Category::query()->create([
            'slug' => 'first',
            'name_en' => 'First',
            'name_ur' => 'اول',
            'image_path' => 'categories/shared.jpg',
        ]);
        $second = Category::query()->create([
            'slug' => 'second',
            'name_en' => 'Second',
            'name_ur' => 'دوم',
            'image_path' => 'categories/shared.jpg',
        ]);
        app(MediaLibraryService::class)->syncModel($first);
        app(MediaLibraryService::class)->syncModel($second);
        $asset = MediaAsset::query()->where('path', 'categories/shared.jpg')->sole();
        $library = app(MediaLibraryService::class);

        try {
            $library->trash($asset);
            $this->fail('A referenced asset was allowed into trash.');
        } catch (\RuntimeException) {
            $this->assertNull($asset->refresh()->trashed_at);
        }

        $first->update(['image_path' => null]);
        $second->update(['image_path' => null]);
        $library->syncModel($first->refresh(), ['image_path']);
        $library->syncModel($second->refresh(), ['image_path']);
        $this->assertModelMissing($asset);
        Storage::disk('public')->assertMissing('categories/shared.jpg');
    }

    public function test_trash_restore_and_expired_cleanup_keep_file_until_quarantine_ends(): void
    {
        Storage::disk('public')->put('media-library/unused.jpg', UploadedFile::fake()->image('unused.jpg')->getContent());
        $library = app(MediaLibraryService::class);
        $asset = $library->registerPath('media-library/unused.jpg', 'unused.jpg');
        $this->assertInstanceOf(MediaAsset::class, $asset);

        $library->trash($asset);
        Storage::disk('public')->assertExists($asset->path);
        $library->restore($asset);
        $this->assertNull($asset->refresh()->trashed_at);

        $library->trash($asset);
        $asset->forceFill(['trashed_at' => now()->subDays(31)])->save();
        $this->artisan('media:prune-trash')->assertSuccessful();
        $this->assertModelMissing($asset);
        Storage::disk('public')->assertMissing($asset->path);
    }

    public function test_permissions_filters_and_page_access_are_enforced(): void
    {
        $viewer = $this->userWithPermissions(['settings.view', 'settings.media.view']);
        $this->actingAs($viewer);
        $this->assertTrue(MediaLibraryPage::canAccess());
        $this->get(MediaLibraryPage::getUrl())->assertOk();
        Livewire::test(Form::class)
            ->dispatch('media-form-open')
            ->set('upload', UploadedFile::fake()->image('blocked.jpg'))
            ->call('save')
            ->assertForbidden();

        $unauthorized = $this->userWithPermissions(['settings.view']);
        $this->actingAs($unauthorized);
        $this->assertFalse(MediaLibraryPage::canAccess());
        Livewire::test(ListMedia::class)->assertForbidden();
    }

    public function test_replacement_syncs_new_usage_before_old_zero_usage_file_is_cleaned(): void
    {
        Storage::disk('public')->put('categories/old.jpg', UploadedFile::fake()->image('old.jpg')->getContent());
        Storage::disk('public')->put('categories/new.jpg', UploadedFile::fake()->image('new.jpg')->getContent());
        $category = Category::withoutEvents(fn () => Category::query()->create([
            'slug' => 'replacement',
            'name_en' => 'Replacement',
            'name_ur' => 'Replacement',
            'image_path' => 'categories/old.jpg',
        ]));
        $library = app(MediaLibraryService::class);
        $library->syncModel($category);

        Category::withoutEvents(fn () => $category->update(['image_path' => 'categories/new.jpg']));
        $library->syncModel($category->refresh(), ['image_path']);
        $library->cleanupUnusedPaths(['categories/old.jpg']);

        Storage::disk('public')->assertMissing('categories/old.jpg');
        Storage::disk('public')->assertExists('categories/new.jpg');
        $this->assertDatabaseHas('media_usages', [
            'usable_type' => $category->getMorphClass(),
            'usable_id' => $category->id,
        ]);
        $this->assertDatabaseMissing('media_assets', ['path' => 'categories/old.jpg']);
    }

    public function test_repair_sweep_is_idempotent_and_preserves_reusable_library_assets(): void
    {
        Storage::disk('public')->put('media-library/reusable.jpg', UploadedFile::fake()->image('reusable.jpg')->getContent());
        Storage::disk('public')->put('categories/orphan.jpg', UploadedFile::fake()->image('orphan.jpg')->getContent());
        $library = app(MediaLibraryService::class);
        $library->registerPath('media-library/reusable.jpg', 'reusable.jpg', sourceModule: 'media-library');
        $library->registerPath('categories/orphan.jpg', 'orphan.jpg', sourceModule: 'categories');

        $this->artisan('media:repair-lifecycle')->assertSuccessful();
        $this->artisan('media:repair-lifecycle')->assertSuccessful();

        Storage::disk('public')->assertExists('media-library/reusable.jpg');
        Storage::disk('public')->assertMissing('categories/orphan.jpg');
        $this->assertDatabaseHas('media_assets', ['path' => 'media-library/reusable.jpg']);
        $this->assertDatabaseMissing('media_assets', ['path' => 'categories/orphan.jpg']);
    }

    public function test_path_referenced_without_usage_rows_cannot_be_trashed(): void
    {
        Storage::disk('public')->put('products/still-used.jpg', UploadedFile::fake()->image('still-used.jpg')->getContent());
        $library = app(MediaLibraryService::class);
        $asset = $library->registerPath('products/still-used.jpg', 'still-used.jpg', sourceModule: 'products');
        $this->assertInstanceOf(MediaAsset::class, $asset);

        \App\Models\Product::withoutEvents(fn () => \App\Models\Product::query()->create([
            'code' => 'P-STILL',
            'slug' => 'still-used-product',
            'name_en' => 'Still Used',
            'name_ur' => 'Still Used',
            'status' => 'active',
            'image_path' => 'products/still-used.jpg',
        ]));

        $this->assertTrue($library->pathIsReferenced('products/still-used.jpg'));
        $this->assertFalse($library->canSafelyTrash($asset));

        try {
            $library->trash($asset);
            $this->fail('Path-referenced asset was allowed into trash.');
        } catch (\RuntimeException) {
            $this->assertNull($asset->refresh()->trashed_at);
        }
    }

    public function test_trash_all_unused_skips_path_referenced_images(): void
    {
        Storage::disk('public')->put('media-library/safe-unused.jpg', UploadedFile::fake()->image('safe-unused.jpg')->getContent());
        Storage::disk('public')->put('products/linked.jpg', UploadedFile::fake()->image('linked.jpg')->getContent());
        $library = app(MediaLibraryService::class);
        $safe = $library->registerPath('media-library/safe-unused.jpg', 'safe-unused.jpg', sourceModule: 'media-library');
        $linked = $library->registerPath('products/linked.jpg', 'linked.jpg', sourceModule: 'products');
        \App\Models\Product::withoutEvents(fn () => \App\Models\Product::query()->create([
            'code' => 'P-LINK',
            'slug' => 'linked-product',
            'name_en' => 'Linked',
            'name_ur' => 'Linked',
            'status' => 'active',
            'image_path' => 'products/linked.jpg',
        ]));

        $this->actingAs($this->userWithPermissions(['settings.view', 'settings.media.view', 'settings.media.manage']));
        Livewire::test(ListMedia::class)
            ->call('applyFilter', 'usage:unused')
            ->call('trashAllUnused')
            ->assertSet('statusType', 'success');

        $this->assertNotNull($safe?->refresh()->trashed_at);
        $this->assertNull($linked?->refresh()->trashed_at);
        Storage::disk('public')->assertExists('products/linked.jpg');
    }

    /**
     * @param  list<string>  $permissions
     */
    private function userWithPermissions(array $permissions): User
    {
        $role = Role::query()->create([
            'name' => 'Media '.uniqid(),
            'slug' => 'media-'.uniqid(),
            'is_system' => false,
        ]);
        $ids = collect($permissions)->map(fn (string $key): int => Permission::query()->firstOrCreate(
            ['key' => $key],
            [
                'group_key' => 'settings',
                'label_en' => $key,
                'label_ur' => $key,
                'sort_order' => 1,
            ],
        )->id);
        $role->permissions()->attach($ids);

        return User::factory()->create([
            'role_id' => $role->id,
            'status' => User::STATUS_ACTIVE,
        ]);
    }
}
