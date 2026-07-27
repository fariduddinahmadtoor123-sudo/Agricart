<?php

namespace App\Http\Middleware;

use App\Models\Role;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class EagerLoadUserRole
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user instanceof User) {
            $user->loadMissing('role');
            $role = $user->role;

            if ($role instanceof Role) {
                $role->setPermissionKeys($this->permissionKeysFor($role));
            }
        }

        return $next($request);
    }

    /**
     * Cache plain permission key strings only — never Eloquent Collections.
     * Serialized Collections break unserialize() in queue workers / shared cache.
     *
     * @return list<string>
     */
    private function permissionKeysFor(Role $role): array
    {
        $version = $role->updated_at?->getTimestamp() ?? 0;
        $cacheKey = 'role_permission_keys:'.$role->id.':'.$version;

        $keys = Cache::remember(
            $cacheKey,
            60,
            fn (): array => $role->permissions()->pluck('key')->all(),
        );

        if (! is_array($keys)) {
            Cache::forget($cacheKey);
            $keys = $role->permissions()->pluck('key')->all();
        }

        return array_values(array_filter(
            $keys,
            fn (mixed $key): bool => is_string($key) && $key !== '',
        ));
    }
}
