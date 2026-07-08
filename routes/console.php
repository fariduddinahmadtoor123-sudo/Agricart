<?php

use App\Models\Customer;
use App\Models\Product;
use App\Services\Sales\OnlineSaleOrderService;
use App\Services\Sales\SaleService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('online-orders:demo', function (): void {
    $customer = Customer::query()->where('customer_channel', Customer::CHANNEL_ONLINE)->first()
        ?? Customer::query()->first();

    $product = Product::query()->where('status', 'active')->first();

    if ($customer === null || $product === null) {
        $this->error('Need at least one customer and one active product.');

        return;
    }

    $saleService = app(SaleService::class);
    $price = '100';

    $order = app(OnlineSaleOrderService::class)->createFromCheckout([
        'customer_id' => $customer->id,
        'locale' => 'en',
        'customer_payment_method' => 'bank_transfer',
        'customer_payment_note' => 'Demo order — test transfer reference 12345',
        'customer_notes' => 'Please call before delivery.',
        'ship_contact_name' => $customer->displayLabel(),
        'ship_phone' => $customer->primaryPhone(),
        'ship_address' => $customer->address_line1 ?? 'Demo address',
        'ship_city' => $customer->city ?? 'Lahore',
        'rows' => [
            [
                'product_id' => $product->id,
                'quantity' => '2',
                'unit_price' => $price,
            ],
        ],
    ]);

    $this->info('Created demo online order: ' . $order->order_number . ' (total ' . $saleService->formatMoney((float) $order->total_amount) . ')');
})->purpose('Create a sample online sale order for admin UI testing');

Artisan::command('staff:attendance-default-day', function (): void {
    $date = now()->startOfDay();
    $created = app(\App\Services\Staff\StaffAttendanceService::class)->ensureDefaultAbsencesForDate($date);

    $this->info('Staff attendance day defaults for ' . $date->toDateString() . ': ' . $created . ' record(s) created.');
})->purpose('Mark all roster staff absent for today when no record exists (run daily after midnight)');

