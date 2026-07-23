<?php

namespace App\Services\Communication\Ports;

use App\Contracts\Communication\CallSessionPort;

class NullCallSession implements CallSessionPort
{
    public function startSession(int $conversationId, string $kind, array $options = []): array
    {
        return [
            'session_id' => null,
            'status' => 'unsupported',
        ];
    }
}
