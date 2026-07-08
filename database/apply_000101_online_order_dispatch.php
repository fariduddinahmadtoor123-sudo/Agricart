<?php

$pdo = new PDO('sqlite:' . __DIR__ . '/database.sqlite');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$cols = $pdo->query('PRAGMA table_info(online_sale_orders)')->fetchAll(PDO::FETCH_ASSOC);
$hasFulfillment = false;

foreach ($cols as $col) {
    if (($col['name'] ?? '') === 'fulfillment_status') {
        $hasFulfillment = true;
        break;
    }
}

if ($hasFulfillment) {
    echo "online order dispatch columns already exist\n";
    exit(0);
}

$pdo->exec('ALTER TABLE online_sale_orders ADD COLUMN assigned_user_id INTEGER NULL REFERENCES users(id) ON DELETE SET NULL');
$pdo->exec('ALTER TABLE online_sale_orders ADD COLUMN fulfillment_status VARCHAR NOT NULL DEFAULT "awaiting_assignment"');

$pdo->exec('CREATE TABLE online_sale_order_supplier_assignments (
    id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
    online_sale_order_id INTEGER NOT NULL,
    supplier_id INTEGER NOT NULL,
    status VARCHAR NOT NULL DEFAULT "pending",
    sent_at DATETIME NULL,
    notes TEXT NULL,
    assigned_by_id INTEGER NULL,
    created_at DATETIME NULL,
    updated_at DATETIME NULL,
    FOREIGN KEY(online_sale_order_id) REFERENCES online_sale_orders(id) ON DELETE CASCADE,
    FOREIGN KEY(supplier_id) REFERENCES suppliers(id) ON DELETE CASCADE,
    FOREIGN KEY(assigned_by_id) REFERENCES users(id) ON DELETE SET NULL,
    UNIQUE(online_sale_order_id, supplier_id)
)');

$pdo->exec('CREATE INDEX online_sale_order_supplier_assignments_supplier_id_status_index ON online_sale_order_supplier_assignments (supplier_id, status)');
$pdo->exec('CREATE INDEX online_sale_orders_fulfillment_status_payment_status_index ON online_sale_orders (fulfillment_status, payment_status)');
$pdo->exec('CREATE INDEX online_sale_orders_assigned_user_id_index ON online_sale_orders (assigned_user_id)');

echo "online order dispatch schema applied\n";
