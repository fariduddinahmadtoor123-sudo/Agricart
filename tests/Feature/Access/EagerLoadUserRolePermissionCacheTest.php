<?php

namespace Tests\Feature\Access;

use App\Http\Middleware\EagerLoadUserRole;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class EagerLoadUserRolePermissionCacheTest extends TestCase
{
    use RefreshDatabase;

    public function test_middleware_caches_permission_keys_not_eloquent_collections(): void
    {
        $role = Role::query()->create([
            'name' => 'Clerk',
            'slug' => 'clerk-'.uniqid(),
            'is_system' => false,
        ]);
        $permission = Permission::query()->create([
            'key' => 'catalog.view',
            'group_key' => 'catalog',
            'label_en' => 'View catalog',
            'label_ur' => 'View catalog',
            'sort_order' => 1,
        ]);
        $role->permissions()->attach($permission->id);

        $user = User::factory()->create([
            'role_id' => $role->id,
            'status' => User::STATUS_ACTIVE,
        ]);

        $request = Request::create('/admin', 'GET');
        $request->setUserResolver(fn () => $user);

        (new EagerLoadUserRole)->handle($request, fn (Request $req) => response('ok'));

        $user->refresh()->load('role');
        (new EagerLoadUserRole)->handle(
            tap(Request::create('/admin', 'GET'), fn (Request $req) => $req->setUserResolver(fn () => $user)),
            function (Request $req) use ($user): mixed {
                $this->assertTrue($user->hasPermission('catalog.view'));
                $this->assertFalse($user->hasPermission('catalog.delete'));
                $this->assertIsArray($user->role?->permissionKeys());
                $this->assertContains('catalog.view', $user->role?->permissionKeys() ?? []);

                $version = $user->role?->updated_at?->getTimestamp() ?? 0;
                $cached = Cache::get('role_permission_keys:'.$user->role_id.':'.$version);
                $this->assertIsArray($cached);
                $this->assertNotInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $cached);

                return response('ok');
            },
        );
    }

    public function test_has_permission_recovers_when_permissions_relation_is_not_a_collection(): void
    {
        $role = Role::query()->create([
            'name' => 'Broken Cache Role',
            'slug' => 'broken-'.uniqid(),
            'is_system' => false,
        ]);
        $permission = Permission::query()->create([
            'key' => 'settings.view',
            'group_key' => 'settings',
            'label_en' => 'View settings',
            'label_ur' => 'View settings',
            'sort_order' => 1,
        ]);
        $role->permissions()->attach($permission->id);

        $user = User::factory()->create([
            'role_id' => $role->id,
            'status' => User::STATUS_ACTIVE,
        ]);
        $user->load('role');
        $user->role->setRelation('permissions', new \stdClass);

        $this->assertTrue($user->hasPermission('settings.view'));
    }
}
