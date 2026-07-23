<?php

namespace App\Services\Communication\Ports;

use App\Contracts\Communication\AiAssistPort;

class NullAiAssist implements AiAssistPort
{
    public function suggestReply(int $conversationId, string $locale = 'en'): ?string
    {
        return null;
    }

    public function summarizeConversation(int $conversationId, string $locale = 'en'): ?string
    {
        return null;
    }

    public function translate(string $text, string $targetLocale): ?string
    {
        return null;
    }
}
