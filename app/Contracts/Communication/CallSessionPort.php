<?php

namespace App\Contracts\Communication;

interface CallSessionPort
{
    /**
     * @param  array<string, mixed>  $options
     * @return array{session_id: string|null, status: string}
     */
    public function startSession(int $conversationId, string $kind, array $options = []): array;
}
