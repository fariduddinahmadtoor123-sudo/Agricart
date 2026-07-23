<?php

namespace App\Services\Store;

use App\Models\Customer;
use App\Models\OnlineSaleOrder;
use App\Notifications\Store\OnlineOrderPlacedNotification;
use App\Notifications\Store\OnlineOrderShippedNotification;

class OnlineStoreOrderNotifier
{
    public function __construct(
        private readonly AgricartInboxService $inbox,
    ) {}

    public function orderPlaced(OnlineSaleOrder $order): void
    {
        $order->loadMissing('customer');

        $customer = $order->customer;

        if (! $customer instanceof Customer) {
            return;
        }

        $this->inbox->notifyOrderPlaced($order);

        $customer->notify(new OnlineOrderPlacedNotification($order));
    }

    public function orderShipped(OnlineSaleOrder $order): void
    {
        $order->loadMissing(['customer', 'supplierAssignments', 'staffAssignments']);

        $customer = $order->customer;

        if (! $customer instanceof Customer) {
            return;
        }

        $this->inbox->notifyOrderShipped($order);

        $customer->notify(new OnlineOrderShippedNotification($order));
    }
}
