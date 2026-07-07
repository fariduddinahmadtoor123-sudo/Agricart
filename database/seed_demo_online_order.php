<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Customer;
use App\Models\Product;
use App\Services\Sales\OnlineSaleOrderService;

$customer = Customer::query()->where('customer_channel', Customer::CHANNEL_ONLINE)->first()
    ?? Customer::query()->first();

$product = Product::query()->where('status', 'active')->first();

if ($customer === null || $product === null) {
    echo "Need customer and active product\n";
    exit(1);
}

$order = app(OnlineSaleOrderService::class)->createFromCheckout([
    'customer_id' => $customer->id,
    'locale' => 'en',
    'customer_payment_method' => 'bank_transfer',
    'customer_payment_note' => 'Demo order — test transfer reference 12345',
    'customer_notes' => 'Please call before delivery.',
    'ship_contact_name' => $customer->displayLabel(),
    'ship_phone' => method_exists($customer, 'primaryPhone') ? $customer->primaryPhone() : null,
    'ship_address' => $customer->address_line1 ?? 'Demo address',
    'ship_city' => $customer->city ?? 'Lahore',
    'rows' => [
        [
            'product_id' => $product->id,
            'quantity' => '2',
            'unit_price' => '100',
        ],
    ],
]);

echo 'Created demo order: ' . $order->order_number . "\n";
