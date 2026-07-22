<?php

namespace Tests\Feature\Staff;

use App\Livewire\Users\Form;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
