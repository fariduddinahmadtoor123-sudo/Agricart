<?php

namespace App\Livewire\Concerns;

use App\Models\User;
use App\Services\Access\ModulePageAccess;

trait AuthorizesSensitiveActions
{
    protected function canAccessAny(array|string $permissions): bool
    {
        $user = auth()->user();

        if (! $user instanceof User) {
            return false;
        }

        if ($user->isSuperAdmin()) {
            return true;
        }

        $permissions = is_array($permissions) ? $permissions : [$permissions];

        foreach ($permissions as $permission) {
            if (is_string($permission) && ModulePageAccess::userHasKey($user, $permission)) {
                return true;
            }
        }

        return false;
    }

    protected function authorizeAny(array|string $permissions): void
    {
        abort_unless($this->canAccessAny($permissions), 403);
    }
}
