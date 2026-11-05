<?php

namespace App\Console\Commands;

use App\Services\Sales\OnlineSaleOrderService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

class CancelUnpaidOnlineOrdersCommand extends Command
{
    protected $signature = 'online-orders:cancel-unpaid
        {--days= : Cancel unpaid pending quotations older than this many days}';

    protected $description = 'Auto-cancel unpaid online quotations after the configured waiting period and notify the customer';

    public function handle(OnlineSaleOrderService $orders): int
    {
        if (! Schema::hasTable('online_sale_orders') || ! Schema::hasColumn('online_sale_orders', 'cancelled_at')) {
            $this->components->warn('Cancellation columns are missing; run migrations first.');

            return self::SUCCESS;
        }

        $days = $this->option('days');
        $days = $days !== null && $days !== ''
            ? max(1, (int) $days)
            : null;

        $cancelled = $orders->cancelUnpaidOlderThan($days);
        $window = $days ?? (int) config('agricart.online_store.unpaid_cancel_days', 7);

        $this->components->info("Cancelled {$cancelled} unpaid online quotation(s) older than {$window} day(s).");

        return self::SUCCESS;
    }
}
