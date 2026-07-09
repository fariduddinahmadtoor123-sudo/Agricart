<?php

namespace App\Services\Store;

use App\Models\Customer;
use App\Models\OnlineSaleOrder;
use App\Notifications\Store\OnlineOrderPlacedNotification;

class OnlineStoreOrderNotifier
{
    public function orderPlaced(OnlineSaleOrder $order): void
    {
        $order->loadMissing('customer');

        $customer = $order->customer;

        if (! $customer instanceof Customer) {
            return;
        }

        $customer->notify(new OnlineOrderPlacedNotification($order));
    }
}
