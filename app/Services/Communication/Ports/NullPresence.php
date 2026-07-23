<?php

namespace App\Services\Communication\Ports;

use App\Contracts\Communication\PresencePort;
use DateTimeInterface;

class NullPresence implements PresencePort
{
    public function markOnline(array $actor): void {}

    public function markOffline(array $actor): void {}

    public function lastSeenAt(array $actor): ?DateTimeInterface
    {
        return null;
    }

    public function setTyping(int $conversationId, array $actor, bool $typing): void {}
}
