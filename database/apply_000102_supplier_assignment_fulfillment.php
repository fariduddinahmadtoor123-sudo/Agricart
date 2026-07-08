<?php

$pdo = new PDO('sqlite:' . __DIR__ . '/database.sqlite');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$cols = $pdo->query('PRAGMA table_info(online_sale_order_supplier_assignments)')->fetchAll(PDO::FETCH_ASSOC);
$hasTracking = false;

foreach ($cols as $col) {
    if (($col['name'] ?? '') === 'tracking_number') {
        $hasTracking = true;
        break;
    }
}

if ($hasTracking) {
    echo "supplier assignment fulfillment columns already exist\n";
    exit(0);
}

$pdo->exec('ALTER TABLE online_sale_order_supplier_assignments ADD COLUMN acknowledged_at DATETIME NULL');
$pdo->exec('ALTER TABLE online_sale_order_supplier_assignments ADD COLUMN shipped_at DATETIME NULL');
$pdo->exec('ALTER TABLE online_sale_order_supplier_assignments ADD COLUMN carrier_name VARCHAR NULL');
$pdo->exec('ALTER TABLE online_sale_order_supplier_assignments ADD COLUMN tracking_number VARCHAR NULL');
$pdo->exec('ALTER TABLE online_sale_order_supplier_assignments ADD COLUMN shipping_reference VARCHAR NULL');
$pdo->exec('ALTER TABLE online_sale_order_supplier_assignments ADD COLUMN shipping_proof_path VARCHAR NULL');
$pdo->exec('ALTER TABLE online_sale_order_supplier_assignments ADD COLUMN supplier_response_notes TEXT NULL');

echo "supplier assignment fulfillment columns applied\n";
