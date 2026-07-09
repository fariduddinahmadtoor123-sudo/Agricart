<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class DeployPreflightCommand extends Command
{
    protected $signature = 'deploy:preflight
                            {--prepare-testing : Enable online store and clear maintenance (store:preflight)}
                            {--link-storage : Attempt storage:link when missing}';

    protected $description = 'Production deployment checks: migrations, storage, storefront, assets';

    public function handle(): int
    {
        $this->components->info('Agricart production preflight');

        $migrationAudit = Artisan::call('migrations:audit-identifiers');
        $this->output->write(Artisan::output());

        if ($migrationAudit !== self::SUCCESS) {
            $this->components->error('Migration identifier audit failed.');

            return self::FAILURE;
        }

        $args = [];
        if ($this->option('prepare-testing')) {
            $args['--prepare-testing'] = true;
        }
        if ($this->option('link-storage')) {
            $args['--link-storage'] = true;
        }

        $storeExit = Artisan::call('store:preflight', $args);
        $this->output->write(Artisan::output());

        return $storeExit === self::SUCCESS ? self::SUCCESS : self::FAILURE;
    }
}
