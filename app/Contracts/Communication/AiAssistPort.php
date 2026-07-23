<?php

namespace App\Contracts\Communication;

interface AiAssistPort
{
    public function suggestReply(int $conversationId, string $locale = 'en'): ?string;

    public function summarizeConversation(int $conversationId, string $locale = 'en'): ?string;

    public function translate(string $text, string $targetLocale): ?string;
}
