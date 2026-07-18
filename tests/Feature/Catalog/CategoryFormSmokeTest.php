<?php

namespace Tests\Feature\Catalog;

use App\Livewire\Categories\Form;
use App\Models\Category;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class CategoryFormSmokeTest extends TestCase
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

    public function test_image_upload_autofills_name_and_category_saves(): void
    {
        Storage::fake('public');

        $component = Livewire::test(Form::class)
            ->call('openForm')
            ->set('imageUpload', UploadedFile::fake()->image('organic-fertilizer.jpg'));

        $component->assertHasNoErrors();
        $component->assertSet('name_en', 'Organic Fertilizer');
        $this->assertNull($component->get('storedImagePath'));
        $this->assertSame([], Storage::disk('public')->allFiles());

        $component
            ->set('name_ur', 'نامیاتی کھاد')
            ->call('saveAndClose')
            ->assertDispatched('categories-saved');

        $category = Category::query()->where('name_en', 'Organic Fertilizer')->firstOrFail();
        $this->assertNotNull($category->image_path);
        Storage::disk('public')->assertExists($category->image_path);
    }
}
