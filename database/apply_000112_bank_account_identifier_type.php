<?php

$pdo = new PDO('sqlite:' . __DIR__ . '/database.sqlite');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$columns = $pdo->query("PRAGMA table_info(bank_accounts)")->fetchAll(PDO::FETCH_ASSOC);
$names = array_column($columns, 'name');

if (in_array('account_identifier_type', $names, true)) {
    echo "account_identifier_type already exists\n";
    exit(0);
}

$pdo->exec("ALTER TABLE bank_accounts ADD COLUMN account_identifier_type VARCHAR NOT NULL DEFAULT 'account_number'");
$pdo->exec("UPDATE bank_accounts SET account_identifier_type = 'iban' WHERE iban IS NOT NULL AND TRIM(iban) != ''");
$pdo->exec("UPDATE bank_accounts SET account_identifier_type = 'account_number' WHERE (iban IS NULL OR TRIM(iban) = '') AND account_number IS NOT NULL AND TRIM(account_number) != ''");

echo "Added account_identifier_type to bank_accounts\n";
