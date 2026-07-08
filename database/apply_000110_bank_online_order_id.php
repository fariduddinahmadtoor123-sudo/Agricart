<?php

$pdo = new PDO('sqlite:' . __DIR__ . '/database.sqlite');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$columns = $pdo->query("PRAGMA table_info(bank_account_transactions)")->fetchAll(PDO::FETCH_ASSOC);
$names = array_column($columns, 'name');

if (in_array('online_sale_order_id', $names, true)) {
    echo "online_sale_order_id already exists\n";
    exit(0);
}

$pdo->exec('ALTER TABLE bank_account_transactions ADD COLUMN online_sale_order_id INTEGER NULL REFERENCES online_sale_orders(id) ON DELETE SET NULL');
$pdo->exec('CREATE INDEX bank_account_transactions_online_sale_order_id_index ON bank_account_transactions (online_sale_order_id)');

echo "Added online_sale_order_id to bank_account_transactions\n";
