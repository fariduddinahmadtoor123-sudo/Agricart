<?php

namespace App\Console\Commands;

use App\Models\SalesChannel;
use App\Services\SalesChannels\SalesChannelService;
use Illuminate\Console\Command;
use Illuminate\Database\Migrations\Migrator;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

class StorePreflightCommand extends Command
{
    protected $signature = 'store:preflight
                            {--prepare-testing : Enable online store, turn maintenance off, clear channel cache}
                            {--link-storage : Run storage:link if the public/storage symlink is missing}';

    protected $description = 'Check environment, assets, and online store settings before hosting a live test';

    public function handle(SalesChannelService $channels): int
    {
        $this->components->info('Agricart storefront preflight');

        $failures = 0;
        $warnings = 0;

        if (config('app.key') === '' || config('app.key') === null) {
            $this->components->error('APP_KEY is missing — run: php artisan key:generate');
            $failures++;
        } else {
            $this->components->twoColumnDetail('APP_KEY', '<fg=green>set</>');
        }

        $appUrl = (string) config('app.url');
        $this->components->twoColumnDetail('APP_URL', $appUrl);

        if (config('app.debug')) {
            $this->components->warn('APP_DEBUG=true — use false on any public/production host.');
            $warnings++;
        } else {
            $this->components->twoColumnDetail('APP_DEBUG', '<fg=green>false</>');
        }

        if (parse_url($appUrl, PHP_URL_SCHEME) !== 'https') {
            $this->components->warn('APP_URL is not https — set https://your-domain after SSL is active.');
            $warnings++;
        } else {
            $this->components->twoColumnDetail('HTTPS (APP_URL)', '<fg=green>yes</>');
        }

        $secureCookies = (bool) config('session.secure');
        if (parse_url($appUrl, PHP_URL_SCHEME) === 'https' && ! $secureCookies) {
            $this->components->warn('SESSION_SECURE_COOKIE should be true when using HTTPS.');
            $warnings++;
        } else {
            $this->components->twoColumnDetail('Secure session cookies', $secureCookies ? '<fg=green>on</>' : 'off (OK for local HTTP)');
        }

        if (! File::exists(public_path('storage')) && ! File::isLink(public_path('storage'))) {
            $this->components->error('public/storage is missing — run: php artisan storage:link');
            $failures++;

            if ($this->option('link-storage')) {
                Artisan::call('storage:link');
                $this->line(Artisan::output());
            }
        } else {
            $this->components->twoColumnDetail('storage:link', '<fg=green>OK</>');
        }

        if (! File::exists(public_path('build/manifest.json'))) {
            $this->components->error('Vite build missing — run: npm run build');
            $failures++;
        } else {
            $this->components->twoColumnDetail('npm run build', '<fg=green>manifest present</>');
        }

        $this->components->twoColumnDetail(
            'public/build in git',
            File::exists(base_path('.git'))
                ? '<fg=yellow>ignored — rebuild on server after each deploy</>'
                : 'n/a',
        );

        if (Schema::hasTable('migrations')) {
            /** @var Migrator $migrator */
            $migrator = app(Migrator::class);
            $files = $migrator->getMigrationFiles([database_path('migrations')]);
            $ran = $migrator->getRepository()->getRan();
            $pending = array_values(array_diff(array_keys($files), $ran));
            $pendingCount = count($pending);

            if ($pendingCount > 0) {
                $this->components->warn("Pending migrations: {$pendingCount} — run: php artisan migrate --force on the server.");
                $warnings++;

                foreach (array_slice($pending, 0, 5) as $name) {
                    $this->line("  · {$name}");
                }

                if ($pendingCount > 5) {
                    $this->line('  · …');
                }
            } else {
                $this->components->twoColumnDetail('Database migrations', '<fg=green>up to date</>');
            }
        } else {
            $this->components->warn('Migrations table missing — run: php artisan migrate on the server.');
            $warnings++;
        }

        if (! Schema::hasTable('online_sale_orders')) {
            $this->components->error('Table online_sale_orders missing — storefront checkout needs migrations.');
            $failures++;
        }

        if (! Schema::hasTable('sales_channels')) {
            $this->components->error('Table sales_channels missing — run migrations before going live.');
            $failures++;
        }

        if (Schema::hasTable('sales_channels')) {
            $channel = SalesChannel::onlineStore();
            $flags = $channels->onlineStoreFlags();

            $this->components->twoColumnDetail('Online store enabled', ($flags['enabled'] ?? false) ? '<fg=green>yes</>' : '<fg=red>no</>');
            $this->components->twoColumnDetail('Maintenance mode', ($flags['maintenance_mode'] ?? false) ? '<fg=red>ON</>' : '<fg=green>off</>');

            if (! ($flags['enabled'] ?? false)) {
                $failures++;
            }

            if ($flags['maintenance_mode'] ?? false) {
                $failures++;
            }

            if ($this->option('prepare-testing')) {
                $channel->update([
                    'is_enabled' => true,
                    'maintenance_mode' => false,
                ]);
                $channels->clearCache();
                $this->components->info('Online store: enabled, maintenance: off (cache cleared).');
            }
        }

        if ($failures > 0) {
            $this->newLine();
            $this->components->error("Preflight failed ({$failures} blocking issue(s)).");

            return self::FAILURE;
        }

        if ($warnings > 0) {
            $this->newLine();
            $this->components->warn("Preflight passed with {$warnings} warning(s). Review before public launch.");
        } else {
            $this->newLine();
            $this->components->info('Preflight passed. You can run a browser test order or: php artisan online-orders:demo');
        }

        return self::SUCCESS;
    }
}
