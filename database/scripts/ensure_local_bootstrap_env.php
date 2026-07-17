<?php

$envPath = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . '.env';
$env = is_file($envPath) ? file_get_contents($envPath) : false;

if ($env === false) {
    fwrite(STDERR, "no .env\n");
    exit(1);
}

if (str_contains($env, 'BACKUP_BOOTSTRAP_ADMIN_EMAIL')) {
    echo "bootstrap_env_skip\n";
    exit(0);
}

file_put_contents(
    $envPath,
    rtrim($env) . "\n\nBACKUP_BOOTSTRAP_ADMIN_EMAIL=admin@agricart.test\nBACKUP_BOOTSTRAP_ADMIN_PASSWORD=password\n",
);

echo "bootstrap_env_added\n";
