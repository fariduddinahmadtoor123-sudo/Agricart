<?php

$pdo = new PDO('sqlite:' . __DIR__ . '/database.sqlite');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$exists = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='sales_channels'")->fetchColumn();

if ($exists) {
    echo "sales_channels table already exists\n";
    exit(0);
}

$pdo->exec('CREATE TABLE sales_channels (
    id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
    slug VARCHAR NOT NULL UNIQUE,
    name_en VARCHAR NOT NULL,
    name_ur VARCHAR NULL,
    is_enabled BOOLEAN NOT NULL DEFAULT 1,
    offline_message_en VARCHAR NULL,
    offline_message_ur VARCHAR NULL,
    banner_message_en VARCHAR NULL,
    banner_message_ur VARCHAR NULL,
    sort_order INTEGER NOT NULL DEFAULT 0,
    created_at DATETIME NULL,
    updated_at DATETIME NULL
)');

$now = date('Y-m-d H:i:s');
$stmt = $pdo->prepare('INSERT INTO sales_channels (slug, name_en, name_ur, is_enabled, offline_message_en, offline_message_ur, banner_message_en, banner_message_ur, sort_order, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
$stmt->execute([
    'online_store',
    'Online Store',
    'آن لائن اسٹور',
    1,
    'Wait for some time',
    'تھوڑی دیر انتظار کریں',
    'Online ordering is temporarily unavailable. You can still browse our catalog.',
    'آن لائن آرڈرنگ عارضی بند ہے۔ آپ مصنوعات دیکھ سکتے ہیں۔',
    1,
    $now,
    $now,
]);

echo "sales_channels schema applied\n";
