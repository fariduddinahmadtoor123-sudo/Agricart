<?php

namespace Tests\Feature\Staff;

use App\Livewire\Users\Form;
use App\Models\MediaAsset;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class StaffFormDocumentPreviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_view_form_includes_cnic_document_preview_urls(): void
    {
        Storage::fake('local');
        Storage::fake('public');

        $admin = $this->superAdmin();
        $staff = User::factory()->create([
            'status' => User::STATUS_ACTIVE,
            'profile_photo_path' => 'users/profile-photos/avatar.webp',
            'cnic_front_path' => 'users/cnic/front.webp',
            'cnic_back_path' => 'users/cnic/back.webp',
        ]);

        Storage::disk('public')->put($staff->profile_photo_path, 'avatar');
        Storage::disk('local')->put($staff->cnic_front_path, 'front');
        Storage::disk('local')->put($staff->cnic_back_path, 'back');

        $frontUrl = route('admin.users.documents.show', ['user' => $staff, 'side' => 'front'], absolute: false);
        $backUrl = route('admin.users.documents.show', ['user' => $staff, 'side' => 'back'], absolute: false);

        Livewire::actingAs($admin)
            ->test(Form::class)
            ->call('openForm', $staff->id, 'view', 'staff')
            ->assertSet('profilePhotoPreviewUrl', Storage::disk('public')->url($staff->profile_photo_path))
            ->assertSet('storedCnicFrontPath', $staff->cnic_front_path)
            ->assertSet('storedCnicBackPath', $staff->cnic_back_path)
            ->assertSet('cnicFrontPreviewUrl', $frontUrl)
            ->assertSet('cnicBackPreviewUrl', $backUrl)
            ->assertSee($frontUrl, false)
            ->assertSee($backUrl, false);
    }

    public function test_staff_document_uploads_use_person_name_and_persist_on_local_disk(): void
    {
        Storage::fake('local');
        Storage::fake('public');

        $admin = $this->superAdmin();
        $staff = User::factory()->create([
            'name' => 'Abdul Baseer',
            'status' => User::STATUS_ACTIVE,
            'role_id' => $admin->role_id,
        ]);

        Livewire::actingAs($admin)
            ->test(Form::class)
            ->call('openForm', $staff->id, 'edit', 'staff')
            ->set('profilePhotoUpload', UploadedFile::fake()->image('camera-roll.jpg', 400, 400))
            ->set('cnicFrontUpload', UploadedFile::fake()->image('scan-front.jpg', 800, 500))
            ->set('cnicBackUpload', UploadedFile::fake()->image('scan-back.jpg', 800, 500))
            ->call('saveAndClose')
            ->assertHasNoErrors();

        $staff->refresh();
        $this->assertNotNull($staff->profile_photo_path);
        $this->assertNotNull($staff->cnic_front_path);
        $this->assertNotNull($staff->cnic_back_path);
        Storage::disk('public')->assertExists($staff->profile_photo_path);
        Storage::disk('local')->assertExists($staff->cnic_front_path);
        Storage::disk('local')->assertExists($staff->cnic_back_path);

        $this->assertDatabaseHas('media_assets', [
            'display_name' => 'Abdul Baseer — Profile photo',
            'path' => $staff->profile_photo_path,
        ]);
        $this->assertDatabaseHas('media_assets', [
            'display_name' => 'Abdul Baseer — ID card front',
            'disk' => 'local',
            'path' => $staff->cnic_front_path,
        ]);
        $this->assertDatabaseHas('media_assets', [
            'display_name' => 'Abdul Baseer — ID card back',
            'disk' => 'local',
            'path' => $staff->cnic_back_path,
        ]);

        $frontAsset = MediaAsset::query()->where('path', $staff->cnic_front_path)->sole();
        $this->assertSame('private', $frontAsset->visibility);
        $this->assertStringContainsString('/admin/users/'.$staff->id.'/documents/front', (string) $frontAsset->url());
    }

    private function superAdmin(): User
    {
        $role = Role::query()->create([
            'name' => 'Super Admin',
            'slug' => 'super-admin',
            'is_system' => true,
        ]);

        return User::factory()->create([
            'role_id' => $role->id,
            'status' => User::STATUS_ACTIVE,
        ]);
    }
}
