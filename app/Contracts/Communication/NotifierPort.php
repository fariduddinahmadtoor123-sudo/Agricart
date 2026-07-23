<?php

namespace App\Contracts\Communication;

use App\Models\Communication\Message;

interface NotifierPort
{
    /**
     * @param  list<array{type: string, id: int|null}>  $recipients
     * @param  array<string, mixed>  $context
     */
    public function notifyNewMessage(Message $message, array $recipients, array $context = []): void;
}
