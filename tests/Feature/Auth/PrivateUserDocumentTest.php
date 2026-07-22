<?php

namespace Tests\Feature\Auth;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PrivateUserDocumentTest extends TestCase
{
    use RefreshDatabase;

    public function test_authorized_admin_can_view_private_cnic_document(): void
    {
        Storage::fake('local');

        $admin = $this->superAdmin();
        $staff = User::factory()->create([
            'status' => User::STATUS_PENDING,
            'cnic_front_path' => 'users/cnic/private-front.webp',
        ]);
        Storage::disk('local')->put($staff->cnic_front_path, 'private-image');

        $response = $this->actingAs($admin)
            ->get(route('admin.users.documents.show', ['user' => $staff, 'side' => 'front']))
            ->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff');

        $cacheControl = (string) $response->headers->get('Cache-Control');
        $this->assertStringContainsString('private', $cacheControl);
        $this->assertStringContainsString('no-store', $cacheControl);
        $this->assertStringContainsString('max-age=0', $cacheControl);
    }

    public function test_cnic_document_requires_authentication_and_permission(): void
    {
        Storage::fake('local');

        $staff = User::factory()->create([
            'status' => User::STATUS_PENDING,
            'cnic_back_path' => 'users/cnic/private-back.webp',
        ]);
        Storage::disk('local')->put($staff->cnic_back_path, 'private-image');

        $this->get(route('admin.users.documents.show', ['user' => $staff, 'side' => 'back']))
            ->assertRedirect('/admin/login');

        $unauthorizedUser = User::factory()->create([
            'status' => User::STATUS_ACTIVE,
            'role_id' => null,
        ]);

        $this->actingAs($unauthorizedUser)
            ->get(route('admin.users.documents.show', ['user' => $staff, 'side' => 'back']))
            ->assertForbidden();
    }

    public function test_cnic_document_falls_back_to_public_disk_when_missing_on_local(): void
    {
        Storage::fake('local');
        Storage::fake('public');

        $admin = $this->superAdmin();
        $staff = User::factory()->create([
            'status' => User::STATUS_ACTIVE,
            'cnic_front_path' => 'users/cnic/legacy-front.webp',
        ]);
        Storage::disk('public')->put($staff->cnic_front_path, 'legacy-public-image');

        $this->actingAs($admin)
            ->get(route('admin.users.documents.show', ['user' => $staff, 'side' => 'front']))
            ->assertOk();
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
