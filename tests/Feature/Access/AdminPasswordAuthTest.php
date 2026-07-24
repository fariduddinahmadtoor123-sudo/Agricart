<?php

namespace Tests\Feature\Access;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminPasswordAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_set_user_password_command_stores_verifiable_hash(): void
    {
        $user = $this->makeAdmin('admin@example.test', 'OldPass123');

        $this->artisan('agricart:set-user-password', [
            'email' => 'Admin@Example.test',
            '--password' => 'NewPass1234',
        ])->assertSuccessful();

        $user->refresh();

        $this->assertTrue(Hash::check('NewPass1234', (string) $user->getRawOriginal('password')));
        $this->assertFalse(Hash::check('OldPass123', (string) $user->getRawOriginal('password')));
    }

    public function test_profile_password_update_does_not_double_hash(): void
    {
        $user = $this->makeAdmin('profile@example.test', 'OldPass123');

        $user->password = 'BrandNewPass1';
        $user->save();

        $hash = (string) $user->fresh()?->getRawOriginal('password');

        $this->assertTrue(Hash::isHashed($hash));
        $this->assertTrue(Hash::check('BrandNewPass1', $hash));
        $this->assertFalse(Hash::check($hash, $hash));
    }

    private function makeAdmin(string $email, string $password): User
    {
        $role = Role::query()->create([
            'name' => 'Super Admin',
            'slug' => 'super-admin',
            'is_system' => true,
        ]);

        return User::query()->create([
            'name' => 'Admin User',
            'email' => $email,
            'password' => $password,
            'status' => User::STATUS_ACTIVE,
            'role_id' => $role->id,
        ]);
    }
}
