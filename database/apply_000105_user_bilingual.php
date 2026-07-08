<?php

$pdo = new PDO('sqlite:' . __DIR__ . '/database.sqlite');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$columns = $pdo->query('PRAGMA table_info(users)')->fetchAll(PDO::FETCH_ASSOC);
$names = array_column($columns, 'name');

if (in_array('name_ur', $names, true)) {
    echo "users bilingual columns already exist\n";
    exit(0);
}

$pdo->exec('ALTER TABLE users ADD COLUMN name_ur VARCHAR NULL');
$pdo->exec('ALTER TABLE users ADD COLUMN address_ur TEXT NULL');

echo "users bilingual columns applied\n";
