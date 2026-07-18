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
            && ($user->isSuperAdmin() || $user->hasPermission('settings.system-errors.view')),
            403,
        );

        return $user;
    }

    protected function authorizeSystemErrorManage(): User
    {
        $user = $this->authorizeSystemErrorView();

        abort_unless(
            $user->isSuperAdmin() || $user->hasPermission('settings.system-errors.manage'),
            403,
        );

        return $user;
    }
}
