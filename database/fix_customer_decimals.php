<?php

$pdo = new PDO('sqlite:' . __DIR__ . '/database.sqlite');
$pdo->exec("UPDATE customers SET opening_balance = 0 WHERE opening_balance IS NULL OR opening_balance = ''");
$pdo->exec("UPDATE customers SET credit_limit = NULL WHERE credit_limit = ''");
echo "Customer decimals cleaned\n";
