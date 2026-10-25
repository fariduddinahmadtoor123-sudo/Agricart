<?php

namespace App\Services\Tools;

use Illuminate\Support\Facades\Cache;

class PendingPaymentsSheetService
{
    public function nextSheetNumber(?int $userId = null): string
    {
        $userId = $userId ?? (int) (auth()->id() ?? 0);
        $key = "agricart.tools.pending_payments.seq.{$userId}";
        $seq = (int) Cache::get($key, 0) + 1;
        Cache::forever($key, $seq);

        return 'PP-'.str_pad((string) $seq, 3, '0', STR_PAD_LEFT);
    }

    public function peekSheetNumber(?int $userId = null): string
    {
        $userId = $userId ?? (int) (auth()->id() ?? 0);
        $seq = (int) Cache::get("agricart.tools.pending_payments.seq.{$userId}", 0) + 1;

        return 'PP-'.str_pad((string) $seq, 3, '0', STR_PAD_LEFT);
    }
}
