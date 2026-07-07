<?php

$pdo = new PDO('sqlite:' . __DIR__ . '/database.sqlite');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$exists = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='sales'")->fetchColumn();

if ($exists) {
    echo "Sales tables already exist\n";
    exit(0);
}

$pdo->exec('CREATE TABLE sales (
    id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
    sale_number VARCHAR NOT NULL,
    customer_id INTEGER NULL,
    cashier_id INTEGER NULL,
    sale_date DATE NOT NULL,
    payment_status VARCHAR NOT NULL DEFAULT "paid",
    payment_method VARCHAR NOT NULL DEFAULT "cash",
    price_display VARCHAR NOT NULL DEFAULT "plain",
    subtotal DECIMAL(14,2) NOT NULL DEFAULT 0,
    discount_type VARCHAR NULL,
    discount_value DECIMAL(14,2) NULL,
    discount_amount DECIMAL(14,2) NOT NULL DEFAULT 0,
    total_amount DECIMAL(14,2) NOT NULL DEFAULT 0,
    amount_paid DECIMAL(14,2) NOT NULL DEFAULT 0,
    print_controls TINYINT(1) NOT NULL DEFAULT 0,
    notes TEXT NULL,
    status VARCHAR NOT NULL DEFAULT "completed",
    created_at DATETIME NULL,
    updated_at DATETIME NULL,
    FOREIGN KEY(customer_id) REFERENCES customers(id) ON DELETE SET NULL,
    FOREIGN KEY(cashier_id) REFERENCES users(id) ON DELETE SET NULL
)');

$pdo->exec('CREATE UNIQUE INDEX sales_sale_number_unique ON sales (sale_number)');
$pdo->exec('CREATE INDEX sales_sale_date_index ON sales (sale_date)');
$pdo->exec('CREATE INDEX sales_customer_id_created_at_index ON sales (customer_id, created_at)');

$pdo->exec('CREATE TABLE sale_items (
    id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
    sale_id INTEGER NOT NULL,
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
    FOREIGN KEY(sale_id) REFERENCES sales(id) ON DELETE CASCADE,
    FOREIGN KEY(product_id) REFERENCES products(id) ON DELETE SET NULL
)');

$pdo->exec('CREATE INDEX sale_items_sale_id_sort_order_index ON sale_items (sale_id, sort_order)');

$batch = (int) $pdo->query('SELECT COALESCE(MAX(batch), 0) + 1 FROM migrations')->fetchColumn();
$insert = $pdo->prepare('INSERT OR IGNORE INTO migrations (migration, batch) VALUES (?, ?)');
$insert->execute(['2026_07_07_000094_create_sales_tables', $batch]);

echo "Sales tables created\n";
