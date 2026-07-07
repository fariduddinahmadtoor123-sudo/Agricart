<?php

$pdo = new PDO('sqlite:' . __DIR__ . '/database.sqlite');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$columns = $pdo->query('PRAGMA table_info(sales)')->fetchAll(PDO::FETCH_ASSOC);
$hasColumn = false;

foreach ($columns as $column) {
    if (($column['name'] ?? '') === 'locale') {
        $hasColumn = true;
        break;
    }
}

if ($hasColumn) {
    echo "sales.locale already exists\n";
    exit(0);
}

$pdo->exec("ALTER TABLE sales ADD COLUMN locale VARCHAR NOT NULL DEFAULT 'en'");

echo "Applied 000096 sales locale\n";
