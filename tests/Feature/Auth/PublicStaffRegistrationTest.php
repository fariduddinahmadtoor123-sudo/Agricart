<?php

namespace Tests\Feature\Auth;

use App\Livewire\Approvals\ListStaffApprovals;
use App\Livewire\Auth\RegisterForm;
use App\Livewire\Users\Form as UserForm;
use App\Models\Role;
use App\Models\User;
use App\Services\Access\BootstrapSuperAdminService;
use App\Services\Access\UserService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class PublicStaffRegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_registration_document_uploads_set_inline_preview_urls(): void
    {
        Storage::fake('public');
        Storage::fake('local');

        $component = Livewire::test(RegisterForm::class)
            ->set('profilePhotoUpload', UploadedFile::fake()->image('Abdul Hadi Profile Picture.jpeg', 400, 400))
            ->set('cnicFrontUpload', UploadedFile::fake()->image('Abdul Hadi Nation Card Front Side.jpeg', 800, 500))
            ->set('cnicBackUpload', UploadedFile::fake()->image('Abdul Hadi Nation Card Backside.jpeg', 800, 500));

        foreach (['profilePhotoPreviewUrl', 'cnicFrontPreviewUrl', 'cnicBackPreviewUrl'] as $property) {
            $preview = $component->get($property);
            $this->assertIsString($preview);
            $this->assertStringStartsWith('data:image/jpeg;base64,', $preview);
        }
    }

    public function test_public_staff_application_is_pending_and_visible_in_approvals(): void
    {
        Storage::fake('public');
        Storage::fake('local');

        $admin = $this->superAdmin();

        Livewire::test(RegisterForm::class)
            ->set('name', 'Public Staff Applicant')
            ->set('name_ur', 'عوامی عملہ درخواست گزار')
            ->set('email', 'Applicant@Example.com')
            ->set('address', '123 Agriculture Road, Lahore')
            ->set('address_ur', '۱۲۳ ایگریکلچر روڈ، لاہور')
            ->set('phoneRows.0.contact_person', 'Public Staff Applicant')
            ->set('phoneRows.0.contact_person_ur', 'عوامی عملہ درخواست گزار')
            ->set('phoneRows.0.phone_number', '03001234567')
            ->set('bankRows.0.bank_name', 'National Bank of Pakistan (NBP)')
            ->set('bankRows.0.account_title', 'Public Staff Applicant')
            ->set('bankRows.0.account_title_ur', 'عوامی عملہ درخواست گزار')
            ->set('bankRows.0.account_number', 'PK00NBPA0000000000000000')
            ->set('password', 'safe-password')
            ->set('password_confirmation', 'safe-password')
            ->set('profilePhotoUpload', UploadedFile::fake()->image('profile.jpg', 500, 500))
            ->set('cnicFrontUpload', UploadedFile::fake()->image('cnic-front.jpg', 800, 500))
            ->set('cnicBackUpload', UploadedFile::fake()->image('cnic-back.jpg', 800, 500))
            ->set('terms_accepted', true)
            ->call('submit')
            ->assertHasNoErrors()
            ->assertSet('submitted', true)
            ->assertSee(__('agricart.auth.register_pending_approval'))
            ->assertSee(__('agricart.auth.register_application_reference'))
            ->assertDispatched('staff-registration-result', success: true);

        $applicant = User::query()->where('email', 'applicant@example.com')->firstOrFail();

        $this->assertSame(User::STATUS_PENDING, $applicant->status);
        $this->assertNull($applicant->role_id);
        $this->assertSame('03001234567', $applicant->phone_numbers[0]['phone_number']);
        $this->assertNotNull($applicant->cnic_front_path);
        $this->assertNotNull($applicant->cnic_back_path);
        Storage::disk('public')->assertExists($applicant->profile_photo_path);
        Storage::disk('local')->assertExists($applicant->cnic_front_path);
        Storage::disk('local')->assertExists($applicant->cnic_back_path);
        Storage::disk('public')->assertMissing($applicant->cnic_front_path);
        Storage::disk('public')->assertMissing($applicant->cnic_back_path);

        Livewire::actingAs($admin)
            ->test(ListStaffApprovals::class)
            ->assertSee('Public Staff Applicant')
            ->assertSee('applicant@example.com');
    }

    public function test_first_public_registration_never_bootstraps_a_super_admin(): void
    {
        $user = app(UserService::class)->registerPublic([
            'name' => 'First Public Applicant',
            'name_ur' => 'پہلا عوامی درخواست گزار',
            'email' => 'first@example.com',
            'address' => 'Multan',
            'address_ur' => 'ملتان',
            'password' => 'safe-password',
            'phone_numbers' => [
                ['contact_person' => 'First Public Applicant', 'contact_person_ur' => 'پہلا عوامی درخواست گزار', 'phone_number' => '03001234567'],
            ],
            'bank_accounts' => [
                [
                    'bank_name' => 'National Bank of Pakistan (NBP)',
                    'account_title' => 'First Public Applicant',
                    'account_title_ur' => 'پہلا عوامی درخواست گزار',
                    'account_number' => 'PK00NBPA0000000000000000',
                ],
            ],
            'profile_photo_path' => 'users/profile-photos/first.webp',
            'cnic_front_path' => 'users/cnic/first-front.webp',
            'cnic_back_path' => 'users/cnic/first-back.webp',
        ]);

        $this->assertSame(User::STATUS_PENDING, $user->status);
        $this->assertNull($user->role_id);
        $this->assertFalse($user->isSuperAdmin());
        $this->assertNotNull($user->loginBlockedMessage());

        $bootstrap = app(BootstrapSuperAdminService::class);

        $this->assertFalse($bootstrap->canAssignBootstrapOwner($user));
        $this->assertFalse($bootstrap->assignBootstrapOwnerIfEligible($user)->isSuperAdmin());
        $this->assertSame(User::STATUS_PENDING, $user->fresh()->status);
    }

    public function test_rejected_applicant_can_submit_complete_corrected_details(): void
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
            'name_ur' => 'درست شدہ درخواست گزار',
            'email' => 'rejected@example.com',
            'address' => 'Corrected Lahore address',
            'address_ur' => 'درست شدہ لاہور کا پتہ',
            'password' => 'new-safe-password',
            'phone_numbers' => [
                ['contact_person' => 'Corrected Applicant', 'contact_person_ur' => 'درست شدہ درخواست گزار', 'phone_number' => '03111234567'],
            ],
            'bank_accounts' => [
                [
                    'bank_name' => 'The Bank of Punjab (BOP)',
                    'account_title' => 'Corrected Applicant',
                    'account_title_ur' => 'درست شدہ درخواست گزار',
                    'account_number' => 'PK00BPUN0000000000000000',
                ],
            ],
            'profile_photo_path' => 'users/profile-photos/corrected.webp',
            'cnic_front_path' => 'users/cnic/corrected-front.webp',
            'cnic_back_path' => 'users/cnic/corrected-back.webp',
        ]);

        $user->refresh();

        $this->assertSame(User::STATUS_PENDING, $user->status);
        $this->assertNull($user->role_id);
        $this->assertNull($user->rejection_reason);
        $this->assertNull($user->rejected_at);
        $this->assertSame('users/cnic/corrected-front.webp', $user->cnic_front_path);
        $this->assertSame('users/cnic/corrected-back.webp', $user->cnic_back_path);
    }

    public function test_public_form_is_compact_and_does_not_render_the_numbered_guidance_banner(): void
    {
        $this->get(route('auth.staff.register'))
            ->assertOk()
            ->assertSee(__('agricart.auth.register_required_note'))
            ->assertDontSee(__('agricart.auth.register_guidance_title'))
            ->assertDontSee(__('agricart.auth.register_step_review'))
            ->assertSee('data-update-uri="/livewire/update"', false)
            ->assertDontSee('window.livewireScriptConfig', false);
    }

    public function test_every_public_application_section_is_required(): void
    {
        $component = Livewire::test(RegisterForm::class)
            ->set('name', 'Incomplete Applicant')
            ->set('email', 'incomplete@example.com')
            ->call('submit')
            ->assertSet('submitted', false)
            ->assertDispatched('staff-registration-result', success: false);

        $this->assertNotNull($component->get('statusMessage'));
        $this->assertDatabaseMissing('users', ['email' => 'incomplete@example.com']);

        try {
            app(UserService::class)->registerPublic([
                'name' => 'Incomplete Applicant',
                'email' => 'incomplete-service@example.com',
                'password' => 'safe-password',
            ]);

            $this->fail('Incomplete public registration unexpectedly passed service validation.');
        } catch (ValidationException $exception) {
            $this->assertEqualsCanonicalizing([
                'name_ur',
                'address',
                'address_ur',
                'phone_numbers',
                'bank_accounts',
                'profile_photo_path',
                'cnic_front_path',
                'cnic_back_path',
            ], array_keys($exception->errors()));
        }
    }

    public function test_bank_picker_opens_with_searchable_banks_microfinance_and_wallets(): void
    {
        Livewire::test(RegisterForm::class)
            ->call('openBankPicker', 0)
            ->assertSet('openBankPickerIndex', 0)
            ->assertSee('Habib Bank Limited (HBL)')
            ->assertSee('HBL Microfinance Bank')
            ->assertSee('JazzCash (Mobilink MFB)')
            ->assertSee('EasyPaisa (Telenor MFB)')
            ->assertSee('Zarai Taraqiati Bank Limited (ZTBL)')
            ->set('bankSearches.0', 'jazz cash')
            ->assertSee('JazzCash (Mobilink MFB)')
            ->call('selectBank', 0, 'JazzCash (Mobilink MFB)')
            ->assertSet('bankRows.0.bank_name', 'JazzCash (Mobilink MFB)')
            ->assertSet('openBankPickerIndex', null)
            ->call('clearBank', 0)
            ->assertSet('bankRows.0.bank_name', '')
            ->assertSet('openBankPickerIndex', 0)
            ->call('selectBank', 0, 'Unlisted Custom Bank')
            ->assertSet('bankRows.0.bank_name', '');
    }

    public function test_unlisted_bank_is_rejected_even_when_livewire_state_is_tampered(): void
    {
        try {
            app(UserService::class)->registerPublic([
                'name' => 'Tampered Applicant',
                'name_ur' => 'تبدیل شدہ درخواست گزار',
                'email' => 'tampered@example.com',
                'address' => 'Lahore',
                'address_ur' => 'لاہور',
                'password' => 'safe-password',
                'phone_numbers' => [
                    ['contact_person' => 'Tampered Applicant', 'contact_person_ur' => 'تبدیل شدہ درخواست گزار', 'phone_number' => '03001234567'],
                ],
                'bank_accounts' => [
                    [
                        'bank_name' => 'Unlisted Custom Bank',
                        'account_title' => 'Tampered Applicant',
                        'account_title_ur' => 'تبدیل شدہ درخواست گزار',
                        'account_number' => 'PK00FAKE0000000000000000',
                    ],
                ],
                'profile_photo_path' => 'users/profile-photos/tampered.webp',
                'cnic_front_path' => 'users/cnic/tampered-front.webp',
                'cnic_back_path' => 'users/cnic/tampered-back.webp',
            ]);

            $this->fail('An unlisted bank unexpectedly passed server-side validation.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('bank_accounts.0.bank_name', $exception->errors());
        }

        $this->assertDatabaseMissing('users', ['email' => 'tampered@example.com']);
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
