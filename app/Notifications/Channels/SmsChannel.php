<?php

namespace App\Notifications\Channels;

use App\Contracts\Store\SmsSender;
use Illuminate\Notifications\Notification;

class SmsChannel
{
    public function __construct(
        private readonly SmsSender $sms,
    ) {}

    /**
     * @param  \App\Models\Customer  $notifiable
     */
    public function send(object $notifiable, Notification $notification): void
    {
        if (! method_exists($notification, 'toSms')) {
            return;
        }

        if (! config('agricart.online_store.order_notifications.sms', true)) {
            return;
        }

        $phone = $notifiable->primaryPhone();

        if (! filled($phone)) {
            return;
        }

        $message = $notification->toSms($notifiable);

        if (! filled($message)) {
            return;
        }

        $this->sms->send($phone, $message);
    }
}
