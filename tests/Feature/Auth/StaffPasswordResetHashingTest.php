<?php

namespace Tests\Feature\Auth;

use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Tests\TestCase;

class StaffPasswordResetHashingTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_password_reset_stores_a_single_hash_that_matches_plain_password(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $user = User::query()->create([
            'user_number' => 'USR-00999',
            'name' => 'Reset User',
            'email' => 'reset-user@agricart.test',
            'password' => 'OldPassword1!',
            'role_id' => Role::query()->where('slug', 'super-admin')->value('id'),
            'status' => User::STATUS_ACTIVE,
        ]);

        Event::fake([PasswordReset::class]);

        $status = Password::broker('users')->reset(
            [
                'email' => $user->email,
                'password' => 'NewPassword2!',
                'password_confirmation' => 'NewPassword2!',
                'token' => Password::broker('users')->createToken($user),
            ],
            function (User $resetUser, string $password): void {
                // Same contract as App\Filament\Auth\Pages\ResetPassword
                $resetUser->forceFill([
                    'password' => $password,
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($resetUser));
            }
        );

        $this->assertSame(Password::PASSWORD_RESET, $status);

        $fresh = $user->fresh();
        $raw = (string) $fresh->getRawOriginal('password');

        $this->assertTrue(Hash::isHashed($raw));
        $this->assertTrue(Hash::check('NewPassword2!', $raw));
        $this->assertFalse(Hash::check('OldPassword1!', $raw));
    }
}
