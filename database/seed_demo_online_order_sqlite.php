<?php

$pdo = new PDO('sqlite:' . __DIR__ . '/database.sqlite');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

if (! $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='online_sale_orders'")->fetchColumn()) {
    echo "Run apply_000099 first\n";
    exit(1);
}

if ((int) $pdo->query('SELECT COUNT(*) FROM online_sale_orders')->fetchColumn() > 0) {
    echo "Online orders already exist — skipped demo insert\n";
    exit(0);
}

$customerId = $pdo->query('SELECT id FROM customers ORDER BY id LIMIT 1')->fetchColumn();
$product = $pdo->query('SELECT id, name_en, name_ur, image_path FROM products WHERE status = "active" ORDER BY id LIMIT 1')->fetch(PDO::FETCH_ASSOC);

if (! $customerId || ! $product) {
    echo "Need a customer and active product for demo order\n";
    exit(1);
}

$now = date('Y-m-d H:i:s');
$today = date('Y-m-d');

$pdo->prepare('INSERT INTO online_sale_orders (
    order_number, customer_id, order_date, locale, order_status, payment_status,
    customer_payment_method, customer_payment_note, subtotal, total_amount,
    ship_contact_name, customer_notes, created_at, updated_at
) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)')->execute([
    'WEB-00001',
    $customerId,
    $today,
    'en',
    'pending',
    'unpaid',
    'bank_transfer',
    'Demo: bank transfer ref 12345',
    200,
    200,
    'Demo customer',
    'Please call before delivery.',
    $now,
    $now,
]);

$orderId = (int) $pdo->lastInsertId();

$pdo->prepare('INSERT INTO online_sale_order_items (
    online_sale_order_id, product_id, sort_order, product_name_en, product_name_ur,
    image_path, quantity, unit_price, price_tier_key, line_total, created_at, updated_at
) VALUES (?, ?, 0, ?, ?, ?, ?, ?, ?, ?, ?, ?)')->execute([
    $orderId,
    $product['id'],
    $product['name_en'],
    $product['name_ur'],
    $product['image_path'],
    '2',
    '100.00',
    'retail',
    '200.00',
    $now,
    $now,
]);

echo "Inserted demo order WEB-00001\n";
