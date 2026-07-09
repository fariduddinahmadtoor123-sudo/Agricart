<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Throwable;

class HostingerVerifyCommand extends Command
{
    protected $signature = 'hostinger:verify
                            {--public-html= : Path to public_html to test resolve-laravel-root.php}';

    protected $description = 'Verify Laravel root resolution for split public_html deployment';

    public function handle(): int
    {
        $publicHtml = $this->option('public-html') ?: config('hostinger.public_html');

        if (! is_string($publicHtml) || $publicHtml === '') {
            $publicHtml = public_path();
            $this->components->warn('HOSTINGER_PUBLIC_HTML not set — verifying from project public/ (local/VPS layout).');
        }

        $publicHtml = rtrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $publicHtml), DIRECTORY_SEPARATOR);

        require_once public_path('agricart-laravel-root.php');

        try {
            $root = agricart_resolve_laravel_root($publicHtml);
        } catch (Throwable $exception) {
            $this->components->error($exception->getMessage());

            return self::FAILURE;
        }

        if (! File::exists($root.'/vendor/autoload.php')) {
            $this->components->error('Invalid root returned by resolver.');

            return self::FAILURE;
        }

        $this->components->twoColumnDetail('Web root (public)', $publicHtml);
        $this->components->twoColumnDetail('Laravel root', $root);
        $this->components->twoColumnDetail('Matches this project', realpath(base_path()) === realpath($root) ? 'yes' : 'no');

        return self::SUCCESS;
    }
}
