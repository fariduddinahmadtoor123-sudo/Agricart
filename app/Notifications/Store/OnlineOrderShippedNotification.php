<?php

namespace App\Notifications\Store;

use App\Mail\Store\OnlineOrderShippedMail;
use App\Models\Customer;
use App\Models\OnlineSaleOrder;
use Illuminate\Notifications\Notification;

class OnlineOrderShippedNotification extends Notification
{
    public function __construct(
        public OnlineSaleOrder $order,
    ) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        $channels = [];

        if (config('agricart.online_store.order_notifications.email', true) && filled($notifiable->email ?? null)) {
            $channels[] = 'mail';
        }

        if (config('agricart.online_store.order_notifications.sms', true) && filled($notifiable->primaryPhone())) {
            $channels[] = \App\Notifications\Channels\SmsChannel::class;
        }

        return $channels;
    }

    public function toMail(Customer $notifiable): OnlineOrderShippedMail
    {
        return new OnlineOrderShippedMail($this->order);
    }

    public function toSms(Customer $notifiable): string
    {
        $parcels = $this->order->customerShippingParcels();
        $first = $parcels[0] ?? null;
        $ref = $first['tracking'] ?? $first['bilty'] ?? null;

        return __('store_notifications.order_shipped.sms', [
            'number' => $this->order->order_number,
            'ref' => $ref ?: '—',
            'store' => config('app.name'),
        ]);
    }
}
