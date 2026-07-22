<?php

namespace App\Mail\Store;

use App\Models\OnlineSaleOrder;
use App\Services\Sales\SaleService;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OnlineOrderShippedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public OnlineSaleOrder $order,
    ) {
        $this->order->loadMissing(['items', 'customer', 'supplierAssignments', 'staffAssignments']);
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('store_notifications.order_shipped.subject', [
                'number' => $this->order->order_number,
                'store' => config('app.name'),
            ]),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.store.online-order-shipped',
            with: [
                'order' => $this->order,
                'sales' => app(SaleService::class),
                'parcels' => $this->order->customerShippingParcels(),
                'orderUrl' => route('store.customer.orders.show', $this->order->order_number),
            ],
        );
    }
}
