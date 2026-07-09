<?php

namespace App\Mail\Store;

use App\Models\OnlineSaleOrder;
use App\Services\Sales\SaleService;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OnlineOrderPlacedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public OnlineSaleOrder $order,
    ) {
        $this->order->loadMissing(['items', 'customer']);
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('store_notifications.order_placed.subject', [
                'number' => $this->order->order_number,
                'store' => config('app.name'),
            ]),
        );
    }

    public function content(): Content
    {
        $sales = app(SaleService::class);

        return new Content(
            markdown: 'mail.store.online-order-placed',
            with: [
                'order' => $this->order,
                'sales' => $sales,
                'orderUrl' => route('store.customer.orders.show', $this->order->order_number),
            ],
        );
    }
}
