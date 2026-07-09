<?php

declare(strict_types=1);

/**
 * Shared hosting path resolver (no Composer autoload required).
 *
 * @throws RuntimeException
 */
function agricart_resolve_laravel_root(string $publicDir): string
{
    if (is_file($override = $publicDir.'/laravel-root.php')) {
        $path = require $override;

        if (is_string($path) && $path !== '' && is_file($path.'/vendor/autoload.php')) {
            $resolved = realpath($path);

            return $resolved !== false ? $resolved : $path;
        }
    }

    $envRoot = getenv('LARAVEL_ROOT') ?: ($_ENV['LARAVEL_ROOT'] ?? false);

    if (is_string($envRoot) && $envRoot !== '' && is_file($envRoot.'/vendor/autoload.php')) {
        $resolved = realpath($envRoot);

        return $resolved !== false ? $resolved : $envRoot;
    }

    if (is_file($publicDir.'/../vendor/autoload.php')) {
        $resolved = realpath($publicDir.'/..');

        return $resolved !== false ? $resolved : $publicDir.'/..';
    }

    $siblingNames = ['agricart', 'Agricart', 'laravel', 'Laravel'];

    if ($extra = getenv('HOSTINGER_APP_FOLDER_NAMES') ?: ($_ENV['HOSTINGER_APP_FOLDER_NAMES'] ?? '')) {
        $siblingNames = array_values(array_unique(array_merge(
            $siblingNames,
            array_filter(array_map(trim(...), explode(',', (string) $extra)))
        )));
    }

    $dir = realpath($publicDir) ?: $publicDir;

    for ($depth = 0; $depth < 8; $depth++) {
        if (is_file($dir.'/vendor/autoload.php')) {
            return $dir;
        }

        $parent = dirname($dir);

        foreach ($siblingNames as $name) {
            $candidate = $parent.DIRECTORY_SEPARATOR.$name;

            if (is_file($candidate.'/vendor/autoload.php')) {
                $resolved = realpath($candidate);

                return $resolved !== false ? $resolved : $candidate;
            }
        }

        if ($parent === $dir) {
            break;
        }

        $dir = $parent;
    }

    throw new RuntimeException(
        'Agricart: Laravel application root not found. Deploy the full project outside public_html, '.
        'run: php artisan hostinger:sync-public, or create laravel-root.php — see docs/HOSTINGER_SHARED_HOSTING.md'
    );
}
