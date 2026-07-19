<?php

namespace App\Jobs;

use App\Models\OnlineSaleOrder;
use App\Services\Store\OnlineStoreOrderNotifier;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class SendOnlineOrderPlacedNotification implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public int $uniqueFor = 3600;

    public function __construct(public int $orderId)
    {
        $this->afterCommit();
    }

    public function uniqueId(): string
    {
        return 'online-order-placed:'.$this->orderId;
    }

    public function handle(OnlineStoreOrderNotifier $notifier): void
    {
        DB::transaction(function () use ($notifier): void {
            $order = OnlineSaleOrder::query()->lockForUpdate()->find($this->orderId);

            if ($order === null || $order->notification_dispatched_at !== null) {
                return;
            }

            $notifier->orderPlaced($order);
            $order->forceFill(['notification_dispatched_at' => now()])->save();
        });
    }
}
