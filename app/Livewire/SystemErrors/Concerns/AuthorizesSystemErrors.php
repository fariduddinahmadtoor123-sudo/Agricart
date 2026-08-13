<?php

namespace App\Livewire\SystemErrors\Concerns;

use App\Models\User;

trait AuthorizesSystemErrors
{
    protected function authorizeSystemErrorView(): User
    {
        $user = auth()->user();

        abort_unless(
            $user instanceof User
            && $user->canUseAdmin()
            && ($user->isSuperAdmin() || \App\Services\Access\ModulePageAccess::can($user, 'settings', 'system-errors', 'view')),
            403,
        );

        return $user;
    }

    protected function authorizeSystemErrorManage(): User
    {
        $user = $this->authorizeSystemErrorView();

        abort_unless(
            $user->isSuperAdmin() || \App\Services\Access\ModulePageAccess::can($user, 'settings', 'system-errors', 'edit'),
            403,
        );

        return $user;
    }
}
