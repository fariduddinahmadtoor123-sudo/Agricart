<?php

use App\Models\OnlineSaleOrder;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        OnlineSaleOrder::query()
            ->where('order_status', OnlineSaleOrder::STATUS_CONVERTED)
            ->whereNull('queued_at')
            ->update([
                'queued_at' => now(),
                'queue_document' => OnlineSaleOrder::QUEUE_DOCUMENT_INVOICE,
                'fulfillment_status' => OnlineSaleOrder::FULFILLMENT_AWAITING,
            ]);
    }

    public function down(): void
    {
        // Irreversible data backfill — leave queued invoices as-is.
    }
};
