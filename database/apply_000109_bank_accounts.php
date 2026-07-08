<?php

$pdo = new PDO('sqlite:' . __DIR__ . '/database.sqlite');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

if ($pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='bank_accounts'")->fetchColumn()) {
    echo "bank_accounts already exists\n";
    exit(0);
}

$pdo->exec(<<<'SQL'
CREATE TABLE bank_accounts (
    id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
    account_code VARCHAR NULL UNIQUE,
    name_en VARCHAR NOT NULL,
    name_ur VARCHAR NULL,
    bank_key VARCHAR NULL,
    bank_name_en VARCHAR NULL,
    bank_name_ur VARCHAR NULL,
    account_title VARCHAR NULL,
    account_number VARCHAR NULL,
    iban VARCHAR NULL,
    opening_balance NUMERIC NOT NULL DEFAULT 0,
    currency VARCHAR NOT NULL DEFAULT 'PKR',
    status VARCHAR NOT NULL DEFAULT 'active',
    notes TEXT NULL,
    created_by INTEGER NULL,
    created_at DATETIME NULL,
    updated_at DATETIME NULL,
    FOREIGN KEY(created_by) REFERENCES users(id) ON DELETE SET NULL
)
SQL);

$pdo->exec('CREATE INDEX bank_accounts_status_index ON bank_accounts (status)');

$pdo->exec(<<<'SQL'
CREATE TABLE bank_account_transactions (
    id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
    bank_account_id INTEGER NOT NULL,
    type VARCHAR NOT NULL,
    direction VARCHAR NOT NULL,
    amount NUMERIC NOT NULL,
    transaction_date DATE NOT NULL,
    counterparty_bank_account_id INTEGER NULL,
    supplier_id INTEGER NULL,
    sale_id INTEGER NULL,
    transfer_group VARCHAR NULL,
    description VARCHAR NULL,
    notes TEXT NULL,
    created_by INTEGER NULL,
    created_at DATETIME NULL,
    updated_at DATETIME NULL,
    FOREIGN KEY(bank_account_id) REFERENCES bank_accounts(id) ON DELETE CASCADE,
    FOREIGN KEY(counterparty_bank_account_id) REFERENCES bank_accounts(id) ON DELETE SET NULL,
    FOREIGN KEY(supplier_id) REFERENCES suppliers(id) ON DELETE SET NULL,
    FOREIGN KEY(sale_id) REFERENCES sales(id) ON DELETE SET NULL,
    FOREIGN KEY(created_by) REFERENCES users(id) ON DELETE SET NULL
)
SQL);

$pdo->exec('CREATE INDEX bank_account_transactions_bank_account_id_transaction_date_index ON bank_account_transactions (bank_account_id, transaction_date)');
$pdo->exec('CREATE INDEX bank_account_transactions_sale_id_index ON bank_account_transactions (sale_id)');
$pdo->exec('CREATE INDEX bank_account_transactions_transfer_group_index ON bank_account_transactions (transfer_group)');

echo "bank_accounts + bank_account_transactions created\n";
