<?php

namespace Tests\Feature\Auth;

use App\Livewire\Approvals\ListStaffApprovals;
use App\Livewire\Auth\RegisterForm;
use App\Livewire\Users\Form as UserForm;
use App\Models\Role;
use App\Models\User;
use App\Services\Access\UserService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class PublicStaffRegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_staff_application_is_pending_and_visible_in_approvals(): void
    {
        Storage::fake('public');

        $admin = $this->superAdmin();

        Livewire::test(RegisterForm::class)
            ->set('name', 'Public Staff Applicant')
            ->set('name_ur', 'عوامی عملہ درخواست گزار')
            ->set('email', 'Applicant@Example.com')
            ->set('address', '123 Agriculture Road, Lahore')
            ->set('phoneRows.0.phone_number', '03001234567')
            ->set('password', 'safe-password')
            ->set('password_confirmation', 'safe-password')
            ->set('cnicFrontUpload', UploadedFile::fake()->image('cnic-front.jpg', 800, 500))
            ->set('cnicBackUpload', UploadedFile::fake()->image('cnic-back.jpg', 800, 500))
            ->call('submit')
            ->assertHasNoErrors()
            ->assertSet('submitted', true);

        $applicant = User::query()->where('email', 'applicant@example.com')->firstOrFail();

        $this->assertSame(User::STATUS_PENDING, $applicant->status);
        $this->assertNull($applicant->role_id);
        $this->assertSame('03001234567', $applicant->phone_numbers[0]['phone_number']);
        $this->assertNotNull($applicant->cnic_front_path);
        $this->assertNotNull($applicant->cnic_back_path);

        Livewire::actingAs($admin)
            ->test(ListStaffApprovals::class)
            ->assertSee('Public Staff Applicant')
            ->assertSee('applicant@example.com');
    }

    public function test_first_public_registration_never_bootstraps_a_super_admin(): void
    {
        $user = app(UserService::class)->registerPublic([
            'name' => 'First Public Applicant',
            'email' => 'first@example.com',
            'address' => 'Multan',
            'password' => 'safe-password',
            'phone_numbers' => [
                ['contact_person' => '', 'contact_person_ur' => '', 'phone_number' => '03001234567'],
            ],
            'bank_accounts' => [],
        ]);

        $this->assertSame(User::STATUS_PENDING, $user->status);
        $this->assertNull($user->role_id);
        $this->assertFalse($user->isSuperAdmin());
    }

    public function test_rejected_applicant_can_resubmit_without_losing_existing_documents(): void
    {
        $user = User::factory()->create([
            'name' => 'Rejected Applicant',
            'email' => 'rejected@example.com',
            'status' => User::STATUS_REJECTED,
            'role_id' => null,
            'cnic_front_path' => 'users/cnic/existing-front.webp',
            'cnic_back_path' => 'users/cnic/existing-back.webp',
            'rejection_reason' => 'Please correct your address.',
            'rejected_at' => now(),
        ]);

        app(UserService::class)->registerPublic([
            'name' => 'Corrected Applicant',
            'email' => 'rejected@example.com',
            'address' => 'Corrected Lahore address',
            'password' => 'new-safe-password',
            'phone_numbers' => [
                ['contact_person' => '', 'contact_person_ur' => '', 'phone_number' => '03111234567'],
            ],
            'bank_accounts' => [],
            'profile_photo_path' => null,
            'cnic_front_path' => null,
            'cnic_back_path' => null,
        ]);

        $user->refresh();

        $this->assertSame(User::STATUS_PENDING, $user->status);
        $this->assertNull($user->role_id);
        $this->assertNull($user->rejection_reason);
        $this->assertNull($user->rejected_at);
        $this->assertSame('users/cnic/existing-front.webp', $user->cnic_front_path);
        $this->assertSame('users/cnic/existing-back.webp', $user->cnic_back_path);
    }

    public function test_public_form_explains_required_fields_and_review_process(): void
    {
        $this->get(route('auth.staff.register'))
            ->assertOk()
            ->assertSee(__('agricart.auth.register_guidance_title'))
            ->assertSee(__('agricart.auth.register_required_note'))
            ->assertSee(__('agricart.auth.register_step_review'));
    }

    public function test_settings_user_form_defaults_new_users_to_pending_approval(): void
    {
        $admin = $this->superAdmin();
        $staffRole = $this->staffRole();

        Livewire::actingAs($admin)
            ->test(UserForm::class)
            ->call('openForm', null, 'create', 'settings')
            ->assertSet('status', User::STATUS_PENDING)
            ->set('name', 'Settings Applicant')
            ->set('email', 'settings-applicant@example.com')
            ->set('role_id', $staffRole->id)
            ->set('password', 'safe-password')
            ->set('password_confirmation', 'safe-password')
            ->call('saveAndClose')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('users', [
            'email' => 'settings-applicant@example.com',
            'status' => User::STATUS_PENDING,
            'role_id' => $staffRole->id,
        ]);
    }

    public function test_staff_module_form_creates_an_active_roster_member(): void
    {
        $admin = $this->superAdmin();
        $staffRole = $this->staffRole();

        Livewire::actingAs($admin)
            ->test(UserForm::class)
            ->call('openForm', null, 'create', 'staff')
            ->assertSet('status', User::STATUS_ACTIVE)
            ->set('name', 'Direct Staff Member')
            ->set('email', 'direct-staff@example.com')
            ->set('role_id', $staffRole->id)
            ->set('password', 'safe-password')
            ->set('password_confirmation', 'safe-password')
            ->call('saveAndClose')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('users', [
            'email' => 'direct-staff@example.com',
            'status' => User::STATUS_ACTIVE,
            'role_id' => $staffRole->id,
        ]);
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

    private function staffRole(): Role
    {
        return Role::query()->create([
            'name' => 'Staff Member',
            'slug' => 'staff-member',
            'is_system' => false,
        ]);
    }
}
