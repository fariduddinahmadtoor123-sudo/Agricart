<?php

namespace App\Http\Middleware;

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

            if ($role !== null) {
                $version = $role->updated_at?->getTimestamp() ?? 0;
                $cacheKey = 'role_permissions:'.$role->id.':'.$version;

                $permissions = Cache::remember(
                    $cacheKey,
                    60,
                    fn () => $role->permissions()->get(),
                );

                $role->setRelation('permissions', $permissions);
            }
        }

        return $next($request);
    }
}
