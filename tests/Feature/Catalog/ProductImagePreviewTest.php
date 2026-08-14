<?php

namespace Tests\Feature\Catalog;

use App\Livewire\Products\Form;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class ProductImagePreviewTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::query()->create([
            'name' => 'Super Admin',
            'slug' => 'super-admin',
            'is_system' => true,
        ]);
        $this->actingAs(User::factory()->create([
            'role_id' => $role->id,
            'status' => User::STATUS_ACTIVE,
        ]));
    }

    public function test_main_product_image_upload_sets_inline_preview_url(): void
    {
        Storage::fake('public');

        $preview = Livewire::test(Form::class)
            ->call('openForm')
            ->set('imageUpload', UploadedFile::fake()->image('drive-chain.jpg', 320, 240))
            ->get('imagePreviewUrl');

        $this->assertIsString($preview);
        $this->assertStringStartsWith('/storage/livewire-previews/', $preview);
    }

    public function test_gallery_image_upload_sets_inline_preview_url(): void
    {
        Storage::fake('public');

        $preview = Livewire::test(Form::class)
            ->call('openForm')
            ->set('galleryUploads.0', UploadedFile::fake()->image('chain-links.jpg', 320, 240))
            ->get('galleryPreviewUrls');

        $this->assertIsString($preview[0] ?? null);
        $this->assertStringStartsWith('/storage/livewire-previews/', $preview[0]);
    }
}
