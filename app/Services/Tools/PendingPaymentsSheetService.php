<?php

namespace App\Services\Tools;

use App\Models\ToolsPendingPaymentSheet;
use Illuminate\Support\Facades\Cache;

class PendingPaymentsSheetService
{
    public function nextSheetNumber(?int $userId = null): string
    {
        $userId = $userId ?? (int) (auth()->id() ?? 0);
        $cacheKey = "agricart.tools.pending_payments.seq.{$userId}";

        $seq = ToolsDocumentSequence::allocate(
            ToolsPendingPaymentSheet::class,
            'sheet_no',
            $userId,
            $cacheKey,
            (int) Cache::get($cacheKey, 0),
            function (int $newSeq) use ($cacheKey): void {
                Cache::forever($cacheKey, $newSeq);
            },
        );

        return ToolsDocumentSequence::format('PP-', $seq);
    }

    public function peekSheetNumber(?int $userId = null): string
    {
        $userId = $userId ?? (int) (auth()->id() ?? 0);
        $cacheKey = "agricart.tools.pending_payments.seq.{$userId}";
        $seq = ToolsDocumentSequence::peek(
            ToolsPendingPaymentSheet::class,
            'sheet_no',
            $userId,
            $cacheKey,
            (int) Cache::get($cacheKey, 0),
        );

        return ToolsDocumentSequence::format('PP-', $seq);
    }
}
