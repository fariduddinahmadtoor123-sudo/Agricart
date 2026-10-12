<?php

declare(strict_types=1);

require __DIR__.'/agricart-laravel-root.php';

try {
    return agricart_resolve_laravel_root(__DIR__);
} catch (Throwable $exception) {
    http_response_code(500);
    header('Content-Type: text/plain; charset=UTF-8');
    exit($exception->getMessage());
}
