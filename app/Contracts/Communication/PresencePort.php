<?php

namespace App\Contracts\Communication;

interface PresencePort
{
    /**
     * @param  array{type: string, id: int|null}  $actor
     */
    public function markOnline(array $actor): void;

    /**
     * @param  array{type: string, id: int|null}  $actor
     */
    public function markOffline(array $actor): void;

    /**
     * @param  array{type: string, id: int|null}  $actor
     */
    public function lastSeenAt(array $actor): ?\DateTimeInterface;

    /**
     * @param  array{type: string, id: int|null}  $actor
     */
    public function setTyping(int $conversationId, array $actor, bool $typing): void;
}
