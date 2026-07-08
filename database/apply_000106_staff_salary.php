<?php

$pdo = new PDO('sqlite:' . __DIR__ . '/database.sqlite');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$exists = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='staff_salary_assignments'")->fetchColumn();

if ($exists) {
    echo "staff_salary_assignments already exists\n";
    exit(0);
}

$pdo->exec(<<<'SQL'
CREATE TABLE staff_salary_assignments (
    id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
    user_id INTEGER NOT NULL,
    monthly_gross NUMERIC NOT NULL,
    currency VARCHAR NOT NULL DEFAULT 'PKR',
    effective_from DATE NOT NULL,
    effective_to DATE NULL,
    notes TEXT NULL,
    created_by INTEGER NULL,
    created_at DATETIME NULL,
    updated_at DATETIME NULL,
    FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY(created_by) REFERENCES users(id) ON DELETE SET NULL
)
SQL);

$pdo->exec('CREATE INDEX staff_salary_assignments_user_id_effective_from_index ON staff_salary_assignments (user_id, effective_from)');

echo "staff_salary_assignments applied\n";
