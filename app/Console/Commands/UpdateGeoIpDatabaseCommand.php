<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use PharData;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use Throwable;

class UpdateGeoIpDatabaseCommand extends Command
{
    protected $signature = 'analytics:geoip-update';

    protected $description = 'Download and install the private MaxMind GeoLite2 City database';

    public function handle(): int
    {
        $accountId = trim((string) config('analytics.maxmind_account_id'));
        $licenseKey = trim((string) config('analytics.maxmind_license_key'));

        if ($accountId === '' || $licenseKey === '') {
            $this->components->warn('MAXMIND_ACCOUNT_ID and MAXMIND_LICENSE_KEY are required.');

            return self::FAILURE;
        }

        $workspace = storage_path('app/private/geoip/update-'.bin2hex(random_bytes(6)));
        $archive = $workspace.'/GeoLite2-City.tar.gz';
        $tar = $workspace.'/GeoLite2-City.tar';
        $extract = $workspace.'/extract';

        try {
            File::ensureDirectoryExists($extract);

            $response = Http::withBasicAuth($accountId, $licenseKey)
                ->timeout(120)
                ->retry(2, 1500)
                ->get('https://download.maxmind.com/geoip/databases/GeoLite2-City/download', [
                    'suffix' => 'tar.gz',
                ])
                ->throw();

            File::put($archive, $response->body());
            (new PharData($archive))->decompress();
            (new PharData($tar))->extractTo($extract, null, true);

            $database = $this->findDatabase($extract);

            if ($database === null) {
                throw new RuntimeException('GeoLite2-City.mmdb was not found in the downloaded archive.');
            }

            $target = (string) config('analytics.geoip_database');
            File::ensureDirectoryExists(dirname($target));
            File::copy($database, $target.'.new');
            File::delete($target);
            File::move($target.'.new', $target);

            $this->components->info('GeoLite2 City database updated.');

            return self::SUCCESS;
        } catch (Throwable $exception) {
            $this->components->error('GeoIP update failed: '.$exception->getMessage());

            return self::FAILURE;
        } finally {
            File::deleteDirectory($workspace);
        }
    }

    private function findDatabase(string $directory): ?string
    {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS),
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getFilename() === 'GeoLite2-City.mmdb') {
                return $file->getPathname();
            }
        }

        return null;
    }
}
