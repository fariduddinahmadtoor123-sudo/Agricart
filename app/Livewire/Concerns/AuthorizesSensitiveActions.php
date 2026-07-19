<?php

namespace App\Livewire\Concerns;

use App\Models\User;

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

        return $user->hasAnyPermission($permissions);
    }

    protected function authorizeAny(array|string $permissions): void
    {
        abort_unless($this->canAccessAny($permissions), 403);
    }
}
