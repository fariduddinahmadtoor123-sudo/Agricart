<?php

namespace Tests\Feature\Catalog;

use App\Livewire\Categories\Form;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class CategoryImagePreviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_category_image_upload_sets_inline_preview_url(): void
    {
        Storage::fake('public');

        $role = Role::query()->create([
            'name' => 'Super Admin',
            'slug' => 'super-admin',
            'is_system' => true,
        ]);
        $this->actingAs(User::factory()->create([
            'role_id' => $role->id,
            'status' => User::STATUS_ACTIVE,
        ]));

        $preview = Livewire::test(Form::class)
            ->call('openForm')
            ->set('imageUpload', UploadedFile::fake()->image('boom-part.jpg', 320, 240))
            ->get('imagePreviewUrl');

        $this->assertIsString($preview);
        $this->assertStringStartsWith('/storage/livewire-previews/', $preview);
        Storage::disk('public')->assertExists(substr($preview, strlen('/storage/')));
    }
}
