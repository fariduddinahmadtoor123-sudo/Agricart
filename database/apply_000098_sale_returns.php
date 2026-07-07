<?php

$pdo = new PDO('sqlite:' . __DIR__ . '/database.sqlite');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

if ($pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='sale_returns'")->fetchColumn()) {
    echo "sale_returns tables already exist\n";
    exit(0);
}

$pdo->exec('CREATE TABLE sale_returns (
    id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
    return_number VARCHAR NOT NULL,
    sale_id INTEGER NOT NULL,
    customer_id INTEGER NULL,
    processed_by_id INTEGER NULL,
    return_date DATE NOT NULL,
    refund_subtotal DECIMAL(14,2) NOT NULL DEFAULT 0,
    refund_total DECIMAL(14,2) NOT NULL DEFAULT 0,
    payment_status VARCHAR NOT NULL DEFAULT "unpaid",
    payment_method VARCHAR NOT NULL DEFAULT "cash",
    bank_account_id INTEGER NULL,
    amount_paid DECIMAL(14,2) NOT NULL DEFAULT 0,
    notes TEXT NULL,
    created_at DATETIME NULL,
    updated_at DATETIME NULL,
    FOREIGN KEY(sale_id) REFERENCES sales(id),
    FOREIGN KEY(customer_id) REFERENCES customers(id) ON DELETE SET NULL,
    FOREIGN KEY(processed_by_id) REFERENCES users(id) ON DELETE SET NULL
)');

$pdo->exec('CREATE UNIQUE INDEX sale_returns_return_number_unique ON sale_returns (return_number)');
$pdo->exec('CREATE INDEX sale_returns_return_date_index ON sale_returns (return_date)');
$pdo->exec('CREATE INDEX sale_returns_customer_id_created_at_index ON sale_returns (customer_id, created_at)');
$pdo->exec('CREATE INDEX sale_returns_sale_id_index ON sale_returns (sale_id)');

$pdo->exec('CREATE TABLE sale_return_items (
    id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
    sale_return_id INTEGER NOT NULL,
    sale_item_id INTEGER NULL,
    product_id INTEGER NULL,
    sort_order INTEGER NOT NULL DEFAULT 0,
    product_name_en VARCHAR NOT NULL,
    product_name_ur VARCHAR NULL,
    brand_name_en VARCHAR NULL,
    brand_name_ur VARCHAR NULL,
    unit_label VARCHAR NULL,
    image_path VARCHAR NULL,
    sold_quantity VARCHAR NULL,
    unit_price VARCHAR NULL,
    return_quantity VARCHAR NULL,
    return_amount VARCHAR NULL,
    created_at DATETIME NULL,
    updated_at DATETIME NULL,
    FOREIGN KEY(sale_return_id) REFERENCES sale_returns(id) ON DELETE CASCADE,
    FOREIGN KEY(sale_item_id) REFERENCES sale_items(id) ON DELETE SET NULL,
    FOREIGN KEY(product_id) REFERENCES products(id) ON DELETE SET NULL
)');

$pdo->exec('CREATE INDEX sale_return_items_sale_return_id_sort_order_index ON sale_return_items (sale_return_id, sort_order)');

echo "Applied 000098 sale_returns\n";
