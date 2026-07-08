<?php

$pdo = new PDO('sqlite:' . __DIR__ . '/database.sqlite');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$columns = $pdo->query('PRAGMA table_info(sales_channels)')->fetchAll(PDO::FETCH_ASSOC);
$names = array_column($columns, 'name');

if (in_array('maintenance_mode', $names, true)) {
    echo "sales_channels maintenance columns already exist\n";
    exit(0);
}

$pdo->exec('ALTER TABLE sales_channels ADD COLUMN maintenance_mode BOOLEAN NOT NULL DEFAULT 0');
$pdo->exec('ALTER TABLE sales_channels ADD COLUMN maintenance_message_en VARCHAR NULL');
$pdo->exec('ALTER TABLE sales_channels ADD COLUMN maintenance_message_ur VARCHAR NULL');

echo "sales_channels maintenance columns applied\n";
