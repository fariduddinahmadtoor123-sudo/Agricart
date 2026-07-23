<?php

namespace App\Services\Communication\Ports;

use App\Contracts\Communication\NotifierPort;
use App\Models\Communication\Message;

class NullNotifier implements NotifierPort
{
    public function notifyNewMessage(Message $message, array $recipients, array $context = []): void
    {
        // Phase 1: in-app / email / SMS / WA adapters arrive later.
    }
}
