<?php

namespace Tests\Feature\Access;

use App\Models\Role;
use App\Models\User;
use App\Services\Access\RoleAssignmentGuard;
use App\Services\Access\StaffApprovalService;
use App\Services\Access\UserService;
use App\Services\Staff\StaffMemberService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class RoleAssignmentGuardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_owner_role_cannot_be_changed_by_anyone(): void
    {
        $owner = $this->makeSuperAdmin('owner@test.test');
        $otherSuper = $this->makeSuperAdmin('other-super@test.test');
        $staffRoleId = $this->nonSuperAdminRoleId();

        $this->assertTrue(app(RoleAssignmentGuard::class)->isOwner($owner));

        $this->expectException(ValidationException::class);

        app(UserService::class)->update($owner, [
            'name' => $owner->name,
            'email' => $owner->email,
            'role_id' => $staffRoleId,
            'status' => User::STATUS_ACTIVE,
        ], $otherSuper);
    }

    public function test_non_owner_cannot_assign_super_admin(): void
    {
        $owner = $this->makeSuperAdmin('owner2@test.test');
        $manager = User::factory()->create([
            'role_id' => $this->nonSuperAdminRoleId(),
            'status' => User::STATUS_ACTIVE,
            'email' => 'manager@test.test',
        ]);
        $pending = User::factory()->create([
            'role_id' => null,
            'status' => User::STATUS_PENDING,
            'email' => 'pending@test.test',
        ]);

        $this->assertTrue(app(RoleAssignmentGuard::class)->isOwner($owner));

        $this->expectException(ValidationException::class);

        app(StaffApprovalService::class)->approve(
            $pending,
            (int) Role::query()->where('slug', 'super-admin')->value('id'),
            $manager,
        );
    }

    public function test_owner_can_assign_super_admin_to_another_user(): void
    {
        $owner = $this->makeSuperAdmin('owner3@test.test');
        $staffRoleId = $this->nonSuperAdminRoleId();
        $peer = User::factory()->create([
            'role_id' => $staffRoleId,
            'status' => User::STATUS_ACTIVE,
            'email' => 'promote-me@test.test',
            'name' => 'Promote Me',
        ]);

        $superId = (int) Role::query()->where('slug', 'super-admin')->value('id');

        $updated = app(UserService::class)->update($peer, [
            'name' => $peer->name,
            'email' => $peer->email,
            'role_id' => $superId,
            'status' => User::STATUS_ACTIVE,
        ], $owner);

        $this->assertTrue($updated->isSuperAdmin());
    }

    public function test_owner_status_cannot_be_deactivated(): void
    {
        $owner = $this->makeSuperAdmin('owner4@test.test');
        $other = $this->makeSuperAdmin('other4@test.test');

        $this->expectException(ValidationException::class);

        app(StaffMemberService::class)->setInactive($owner, $other);
    }

    public function test_super_admin_role_hidden_from_non_owner_options(): void
    {
        $owner = $this->makeSuperAdmin('owner5@test.test');
        $staff = User::factory()->create([
            'role_id' => $this->nonSuperAdminRoleId(),
            'status' => User::STATUS_ACTIVE,
            'email' => 'staff5@test.test',
        ]);

        $guard = app(RoleAssignmentGuard::class);
        $superId = (int) Role::query()->where('slug', 'super-admin')->value('id');

        $this->assertArrayHasKey($superId, $guard->assignableRoleOptions($owner));
        $this->assertArrayNotHasKey($superId, $guard->assignableRoleOptions($staff));
    }

    private function makeSuperAdmin(string $email): User
    {
        return User::factory()->create([
            'role_id' => Role::query()->where('slug', 'super-admin')->value('id'),
            'status' => User::STATUS_ACTIVE,
            'email' => $email,
        ]);
    }

    private function nonSuperAdminRoleId(): int
    {
        $role = Role::query()->where('slug', '!=', 'super-admin')->orderBy('id')->first()
            ?? Role::query()->create([
                'name' => 'Staff',
                'slug' => 'staff-'.uniqid(),
                'is_system' => false,
            ]);

        return (int) $role->id;
    }
}
