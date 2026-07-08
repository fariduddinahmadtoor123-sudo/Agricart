<?php

$pdo = new PDO('sqlite:' . __DIR__ . '/database.sqlite');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

if ($pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='staff_advance_transactions'")->fetchColumn()) {
    echo "staff_advance_transactions already exists\n";
    exit(0);
}

$pdo->exec(<<<'SQL'
CREATE TABLE staff_advance_transactions (
    id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
    user_id INTEGER NOT NULL,
    type VARCHAR NOT NULL,
    amount NUMERIC NOT NULL,
    transaction_date DATE NOT NULL,
    notes TEXT NULL,
    created_by INTEGER NULL,
    created_at DATETIME NULL,
    updated_at DATETIME NULL,
    FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY(created_by) REFERENCES users(id) ON DELETE SET NULL
)
SQL);

$pdo->exec('CREATE INDEX staff_advance_transactions_user_id_transaction_date_index ON staff_advance_transactions (user_id, transaction_date)');
$pdo->exec('CREATE INDEX staff_advance_transactions_transaction_date_index ON staff_advance_transactions (transaction_date)');

echo "staff_advance_transactions created\n";
