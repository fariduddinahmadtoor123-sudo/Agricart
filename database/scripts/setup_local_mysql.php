<?php

declare(strict_types=1);

/**
 * Create a local MySQL database for Laragon / dev (matches production backup format).
 *
 * Usage: php database/scripts/setup_local_mysql.php
 *
 * Then set in .env:
 *   DB_CONNECTION=mysql
 *   DB_HOST=127.0.0.1
 *   DB_PORT=3306
 *   DB_DATABASE=agricart
 *   DB_USERNAME=root
 *   DB_PASSWORD=
 *
 * Run: php artisan migrate --force
 *      php database/scripts/ensure_dev_super_admin.php
 */

$host = getenv('DB_HOST') ?: '127.0.0.1';
$port = (int) (getenv('DB_PORT') ?: 3306);
$user = getenv('DB_USERNAME') ?: 'root';
$pass = getenv('DB_PASSWORD') ?: '';
$database = getenv('DB_DATABASE') ?: 'agricart';

$dsn = sprintf('mysql:host=%s;port=%d;charset=utf8mb4', $host, $port);

try {
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
} catch (PDOException $exception) {
    fwrite(STDERR, "Could not connect to MySQL at {$host}:{$port} as {$user}\n");
    fwrite(STDERR, $exception->getMessage()."\n");
    fwrite(STDERR, "Start MySQL in Laragon, then run this script again.\n");
    exit(1);
}

$pdo->exec(
    "CREATE DATABASE IF NOT EXISTS `{$database}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci",
);

echo "MySQL database ready: {$database}\n\n";
echo "Add or update these lines in your .env file:\n\n";
echo "DB_CONNECTION=mysql\n";
echo "DB_HOST={$host}\n";
echo "DB_PORT={$port}\n";
echo "DB_DATABASE={$database}\n";
echo "DB_USERNAME={$user}\n";
echo "DB_PASSWORD={$pass}\n\n";
echo "Then run:\n";
echo "  php artisan config:clear\n";
echo "  php artisan migrate --force\n";
echo "  php database/scripts/ensure_dev_super_admin.php\n\n";
echo "Backups: use MySQL on local AND hosting so .zip backups (database.sql) restore correctly.\n";
echo "SQLite is only for automated tests — do not restore hosting backups onto SQLite.\n";
