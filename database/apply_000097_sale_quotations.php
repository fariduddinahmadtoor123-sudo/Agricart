<?php

$pdo = new PDO('sqlite:' . __DIR__ . '/database.sqlite');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$exists = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='sale_quotations'")->fetchColumn();

if ($exists) {
    echo "sale_quotations tables already exist\n";
    exit(0);
}

$pdo->exec('CREATE TABLE sale_quotations (
    id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
    quotation_number VARCHAR NOT NULL,
    customer_id INTEGER NULL,
    prepared_by_id INTEGER NULL,
    quotation_date DATE NOT NULL,
    locale VARCHAR NOT NULL DEFAULT "en",
    subtotal DECIMAL(14,2) NOT NULL DEFAULT 0,
    discount_type VARCHAR NULL,
    discount_value DECIMAL(14,2) NULL,
    discount_amount DECIMAL(14,2) NOT NULL DEFAULT 0,
    quotation_amount DECIMAL(14,2) NOT NULL DEFAULT 0,
    print_controls TINYINT(1) NOT NULL DEFAULT 0,
    notes TEXT NULL,
    created_at DATETIME NULL,
    updated_at DATETIME NULL,
    FOREIGN KEY(customer_id) REFERENCES customers(id) ON DELETE SET NULL,
    FOREIGN KEY(prepared_by_id) REFERENCES users(id) ON DELETE SET NULL
)');

$pdo->exec('CREATE UNIQUE INDEX sale_quotations_quotation_number_unique ON sale_quotations (quotation_number)');
$pdo->exec('CREATE INDEX sale_quotations_quotation_date_index ON sale_quotations (quotation_date)');
$pdo->exec('CREATE INDEX sale_quotations_customer_id_created_at_index ON sale_quotations (customer_id, created_at)');

$pdo->exec('CREATE TABLE sale_quotation_items (
    id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
    sale_quotation_id INTEGER NOT NULL,
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
    FOREIGN KEY(sale_quotation_id) REFERENCES sale_quotations(id) ON DELETE CASCADE,
    FOREIGN KEY(product_id) REFERENCES products(id) ON DELETE SET NULL
)');

$pdo->exec('CREATE INDEX sale_quotation_items_sale_quotation_id_sort_order_index ON sale_quotation_items (sale_quotation_id, sort_order)');

echo "Applied 000097 sale_quotations\n";
