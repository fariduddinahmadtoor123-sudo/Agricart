<?php

$pdo = new PDO('sqlite:' . __DIR__ . '/database.sqlite');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$columns = $pdo->query('PRAGMA table_info(sales)')->fetchAll(PDO::FETCH_ASSOC);
$hasColumn = false;

foreach ($columns as $column) {
    if (($column['name'] ?? '') === 'bank_account_id') {
        $hasColumn = true;
        break;
    }
}

if ($hasColumn) {
    echo "sales.bank_account_id already exists\n";
    exit(0);
}

$pdo->exec('ALTER TABLE sales ADD COLUMN bank_account_id INTEGER NULL');

echo "Applied 000095 sales bank_account_id\n";
