<?php

$pdo = new PDO('sqlite:' . __DIR__ . '/database.sqlite');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$indexes = $pdo->query("SELECT name FROM sqlite_master WHERE type='index' AND tbl_name='bank_accounts'")->fetchAll(PDO::FETCH_COLUMN);

if (in_array('bank_accounts_account_number_unique', $indexes, true)) {
    echo "Unique index already exists\n";
    exit(0);
}

$pdo->exec('CREATE UNIQUE INDEX bank_accounts_account_number_unique ON bank_accounts (account_number)');

echo "Created unique index on bank_accounts.account_number\n";
