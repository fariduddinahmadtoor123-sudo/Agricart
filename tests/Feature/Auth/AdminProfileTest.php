<?php

namespace Tests\Feature\Auth;

use App\Filament\Auth\EditProfile;
use App\Models\Role;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class AdminProfileTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::query()->create([
            'name' => 'Super Admin',
            'slug' => 'super-admin',
            'is_system' => true,
        ]);

        $this->user = User::factory()->create([
            'role_id' => $role->id,
            'status' => User::STATUS_ACTIVE,
            'password' => 'password',
            'phone_numbers' => [
                [
                    'contact_person' => 'Old Name',
                    'contact_person_ur' => '',
                    'phone_number' => '03001234567',
                ],
            ],
        ]);

        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->actingAs($this->user);
    }

    public function test_admin_profile_is_available_from_the_panel(): void
    {
        $this->get(route('filament.admin.auth.profile'))
            ->assertOk()
            ->assertSee(__('agricart.admin_profile.title'));
    }

    public function test_user_can_update_profile_phone_and_password_with_current_password(): void
    {
        Livewire::test(EditProfile::class)
            ->fillForm([
                'name' => 'Updated User',
                'name_ur' => 'اپ ڈیٹ صارف',
                'email' => $this->user->email,
                'address' => 'Updated address',
                'address_ur' => 'نیا پتہ',
                'primary_phone' => '+923001234567',
                'password' => 'NewStrongPassword123!',
                'passwordConfirmation' => 'NewStrongPassword123!',
                'currentPassword' => 'password',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->user->refresh();

        $this->assertSame('Updated User', $this->user->name);
        $this->assertSame('+923001234567', $this->user->phone_numbers[0]['phone_number']);
        $this->assertTrue(Hash::check('NewStrongPassword123!', $this->user->password));
        $this->assertNull($this->user->remember_token);
    }

    public function test_current_password_is_required_to_change_password(): void
    {
        Livewire::test(EditProfile::class)
            ->fillForm([
                'password' => 'NewStrongPassword123!',
                'passwordConfirmation' => 'NewStrongPassword123!',
                'currentPassword' => 'wrong-password',
            ])
            ->call('save')
            ->assertHasFormErrors(['currentPassword']);

        $this->assertTrue(Hash::check('password', $this->user->refresh()->password));
    }
}
