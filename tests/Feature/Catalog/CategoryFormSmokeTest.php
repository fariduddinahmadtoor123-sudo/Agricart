<?php

namespace Tests\Feature\Catalog;

use App\Livewire\Categories\Form;
use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class CategoryFormSmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_image_upload_autofills_name_and_category_saves(): void
    {
        Storage::fake('public');

        $component = Livewire::test(Form::class)
            ->call('openForm')
            ->set('imageUpload', UploadedFile::fake()->image('organic-fertilizer.jpg'));

        $component->assertHasNoErrors();
        $component->assertSet('name_en', 'Organic Fertilizer');
        $this->assertNotNull($component->get('storedImagePath'));

        $component
            ->set('name_ur', 'نامیاتی کھاد')
            ->call('saveAndClose')
            ->assertDispatched('categories-saved');

        $this->assertSame(1, Category::query()->where('name_en', 'Organic Fertilizer')->count());
    }
}
