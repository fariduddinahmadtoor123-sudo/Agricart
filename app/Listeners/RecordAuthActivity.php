<?php

namespace App\Listeners;

use App\Models\User;
use App\Services\Activity\ActivityLogger;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;

class RecordAuthActivity
{
    public function __construct(
        private readonly ActivityLogger $logger,
    ) {}

    public function onLogin(Login $event): void
    {
        if ($event->guard !== 'web' || ! $event->user instanceof User) {
            return;
        }

        $this->logger->login($event->user);
    }

    public function onLogout(Logout $event): void
    {
        if ($event->guard !== 'web') {
            return;
        }

        $user = $event->user instanceof User ? $event->user : null;
        $this->logger->logout($user);
    }

    public function onFailed(Failed $event): void
    {
        if ($event->guard !== 'web') {
            return;
        }

        $this->logger->failedLogin($event->credentials);
    }
}
