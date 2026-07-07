<?php

$pdo = new PDO('sqlite:' . __DIR__ . '/database.sqlite');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$cols = $pdo->query('PRAGMA table_info(customers)')->fetchAll(PDO::FETCH_COLUMN, 1);

if (in_array('customer_channel', $cols, true)) {
    echo "Migration already applied\n";
    exit(0);
}

$pdo->exec("ALTER TABLE customers ADD COLUMN customer_channel VARCHAR(16) NOT NULL DEFAULT 'online'");
$pdo->exec('ALTER TABLE customers ADD COLUMN display_name VARCHAR(255) NULL');
$pdo->exec('ALTER TABLE customers ADD COLUMN name_en VARCHAR(255) NULL');
$pdo->exec('ALTER TABLE customers ADD COLUMN name_ur VARCHAR(255) NULL');
$pdo->exec('ALTER TABLE customers ADD COLUMN address_en TEXT NULL');
$pdo->exec('ALTER TABLE customers ADD COLUMN address_ur TEXT NULL');
$pdo->exec('ALTER TABLE customers ADD COLUMN address_quick VARCHAR(255) NULL');
$pdo->exec('ALTER TABLE customers ADD COLUMN city_key VARCHAR(255) NULL');
$pdo->exec('ALTER TABLE customers ADD COLUMN city_en VARCHAR(255) NULL');
$pdo->exec('ALTER TABLE customers ADD COLUMN city_ur VARCHAR(255) NULL');
$pdo->exec('ALTER TABLE customers ADD COLUMN opening_balance DECIMAL(15,2) NOT NULL DEFAULT 0');
$pdo->exec('ALTER TABLE customers ADD COLUMN credit_limit DECIMAL(15,2) NULL');
$pdo->exec('ALTER TABLE customers ADD COLUMN contacts TEXT NULL');
$pdo->exec('ALTER TABLE customers ADD COLUMN profile_photo_path VARCHAR(255) NULL');
$pdo->exec('ALTER TABLE customers ADD COLUMN ai_status VARCHAR(255) NOT NULL DEFAULT "none"');

$stmt = $pdo->query('SELECT id, first_name, last_name, phone, city FROM customers');

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $display = trim(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? ''));
    $phone = trim((string) ($row['phone'] ?? ''));
    $contacts = $phone !== '' ? json_encode([['mobile_number' => $phone]], JSON_UNESCAPED_UNICODE) : null;

    $up = $pdo->prepare('UPDATE customers SET customer_channel = ?, display_name = ?, name_en = ?, contacts = ?, city_en = ? WHERE id = ?');
    $up->execute([
        'online',
        $display !== '' ? $display : null,
        $display !== '' ? $display : null,
        $contacts,
        trim((string) ($row['city'] ?? '')) !== '' ? $row['city'] : null,
        $row['id'],
    ]);
}

$batch = (int) $pdo->query('SELECT COALESCE(MAX(batch), 0) + 1 FROM migrations')->fetchColumn();
$insert = $pdo->prepare('INSERT OR IGNORE INTO migrations (migration, batch) VALUES (?, ?)');
$insert->execute(['2026_07_07_000093_extend_customers_for_contacts_admin', $batch]);

echo "Migration applied\n";
