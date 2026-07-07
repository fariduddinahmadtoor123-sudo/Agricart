<?php

$pdo = new PDO('sqlite:' . __DIR__ . '/database.sqlite');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

if ($pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='online_sale_orders'")->fetchColumn()) {
    echo "online_sale_orders tables already exist\n";
    exit(0);
}

$pdo->exec('CREATE TABLE online_sale_orders (
    id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
    order_number VARCHAR NOT NULL,
    customer_id INTEGER NULL,
    sale_id INTEGER NULL,
    order_date DATE NOT NULL,
    locale VARCHAR NOT NULL DEFAULT "en",
    order_status VARCHAR NOT NULL DEFAULT "pending",
    payment_status VARCHAR NOT NULL DEFAULT "unpaid",
    customer_payment_method VARCHAR NULL,
    customer_payment_note TEXT NULL,
    payment_method VARCHAR NULL,
    bank_account_id INTEGER NULL,
    subtotal DECIMAL(14,2) NOT NULL DEFAULT 0,
    total_amount DECIMAL(14,2) NOT NULL DEFAULT 0,
    amount_paid DECIMAL(14,2) NOT NULL DEFAULT 0,
    ship_contact_name VARCHAR NULL,
    ship_phone VARCHAR NULL,
    ship_address TEXT NULL,
    ship_city VARCHAR NULL,
    customer_notes TEXT NULL,
    admin_notes TEXT NULL,
    converted_at DATETIME NULL,
    converted_by_id INTEGER NULL,
    created_at DATETIME NULL,
    updated_at DATETIME NULL,
    FOREIGN KEY(customer_id) REFERENCES customers(id) ON DELETE SET NULL,
    FOREIGN KEY(sale_id) REFERENCES sales(id) ON DELETE SET NULL,
    FOREIGN KEY(converted_by_id) REFERENCES users(id) ON DELETE SET NULL
)');

$pdo->exec('CREATE UNIQUE INDEX online_sale_orders_order_number_unique ON online_sale_orders (order_number)');
$pdo->exec('CREATE INDEX online_sale_orders_order_date_index ON online_sale_orders (order_date)');
$pdo->exec('CREATE INDEX online_sale_orders_order_status_created_at_index ON online_sale_orders (order_status, created_at)');
$pdo->exec('CREATE INDEX online_sale_orders_customer_id_created_at_index ON online_sale_orders (customer_id, created_at)');

$pdo->exec('CREATE TABLE online_sale_order_items (
    id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
    online_sale_order_id INTEGER NOT NULL,
    product_id INTEGER NULL,
    sort_order INTEGER NOT NULL DEFAULT 0,
    product_name_en VARCHAR NOT NULL,
    product_name_ur VARCHAR NULL,
    brand_name_en VARCHAR NULL,
    brand_name_ur VARCHAR NULL,
    unit_label VARCHAR NULL,
    image_path VARCHAR NULL,
    quantity VARCHAR NULL,
    unit_price VARCHAR NULL,
    price_tier_key VARCHAR NULL,
    line_total VARCHAR NULL,
    created_at DATETIME NULL,
    updated_at DATETIME NULL,
    FOREIGN KEY(online_sale_order_id) REFERENCES online_sale_orders(id) ON DELETE CASCADE,
    FOREIGN KEY(product_id) REFERENCES products(id) ON DELETE SET NULL
)');

$pdo->exec('CREATE INDEX online_sale_order_items_online_sale_order_id_sort_order_index ON online_sale_order_items (online_sale_order_id, sort_order)');

echo "Applied 000099 online_sale_orders\n";
