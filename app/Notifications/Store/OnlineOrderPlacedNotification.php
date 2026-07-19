<?php

namespace App\Notifications\Store;

use App\Mail\Store\OnlineOrderPlacedMail;
use App\Models\Customer;
use App\Models\OnlineSaleOrder;
use Illuminate\Notifications\Notification;

class OnlineOrderPlacedNotification extends Notification
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

    public function toMail(Customer $notifiable): OnlineOrderPlacedMail
    {
        return new OnlineOrderPlacedMail($this->order);
    }

    public function toSms(Customer $notifiable): string
    {
        return __('store_notifications.order_placed.sms', [
            'number' => $this->order->order_number,
            'total' => app(\App\Services\Sales\SaleService::class)->formatMoney((float) $this->order->total_amount),
            'store' => config('app.name'),
        ]);
    }
}
