<?php

$pdo = new PDO('sqlite:' . __DIR__ . '/database.sqlite');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

if ($pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='staff_attendance_records'")->fetchColumn()) {
    echo "staff attendance tables already exist\n";
    exit(0);
}

$pdo->exec(<<<'SQL'
CREATE TABLE staff_leave_settings (
    id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
    monthly_paid_leaves INTEGER NOT NULL DEFAULT 2,
    created_at DATETIME NULL,
    updated_at DATETIME NULL
)
SQL);

$pdo->exec("INSERT INTO staff_leave_settings (monthly_paid_leaves, created_at, updated_at) VALUES (2, datetime('now'), datetime('now'))");

$pdo->exec(<<<'SQL'
CREATE TABLE staff_public_holidays (
    id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
    holiday_date DATE NOT NULL UNIQUE,
    title_en VARCHAR NOT NULL,
    title_ur VARCHAR NULL,
    is_paid INTEGER NOT NULL DEFAULT 1,
    notes TEXT NULL,
    created_by INTEGER NULL,
    created_at DATETIME NULL,
    updated_at DATETIME NULL,
    FOREIGN KEY(created_by) REFERENCES users(id) ON DELETE SET NULL
)
SQL);

$pdo->exec(<<<'SQL'
CREATE TABLE staff_attendance_records (
    id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
    user_id INTEGER NOT NULL,
    attendance_date DATE NOT NULL,
    status VARCHAR NOT NULL,
    leave_type VARCHAR NULL,
    is_paid_day INTEGER NOT NULL DEFAULT 1,
    notes TEXT NULL,
    marked_by INTEGER NULL,
    created_at DATETIME NULL,
    updated_at DATETIME NULL,
    FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY(marked_by) REFERENCES users(id) ON DELETE SET NULL,
    UNIQUE(user_id, attendance_date)
)
SQL);

$pdo->exec('CREATE INDEX staff_attendance_records_attendance_date_index ON staff_attendance_records (attendance_date)');

echo "staff attendance & leave tables applied\n";
