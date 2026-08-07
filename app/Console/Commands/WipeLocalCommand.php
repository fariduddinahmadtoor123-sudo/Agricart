<?php

namespace App\Console\Commands;

use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * Destructive local/testing reset: wipe business data, keep Super Admin users.
 * Never runs in production or staging.
 */
class WipeLocalCommand extends Command
{
    protected $signature = 'agricart:wipe-local
        {--keep-super-admins : Keep users with the super-admin role (required for the intended wipe)}
        {--force : Skip confirmation prompt}';

    protected $description = 'Wipe local business data (keep Super Admins). Local/testing only.';

    /** @var list<string> */
    private const PRESERVED_TABLES = [
        'migrations',
    ];

    public function handle(): int
    {
        if (! app()->environment(['local', 'testing'])) {
            $this->components->error('agricart:wipe-local is blocked outside local/testing.');

            return self::FAILURE;
        }

        if (! $this->option('keep-super-admins')) {
            $this->components->error('Refusing to run without --keep-super-admins (safety guard).');

            return self::FAILURE;
        }

        if (! $this->option('force') && ! $this->confirm('This will DELETE almost all local data. Super Admins will be kept. Continue?')) {
            $this->components->warn('Aborted.');

            return self::SUCCESS;
        }

        $superAdmins = $this->captureSuperAdmins();

        if ($superAdmins === []) {
            $this->components->error('No Super Admin users found. Aborting so you are not locked out.');

            return self::FAILURE;
        }

        $this->components->info('Keeping '.count($superAdmins).' Super Admin user(s).');

        try {
            $this->wipeDatabaseTables();
            (new RolesAndPermissionsSeeder)->run();
            $this->restoreSuperAdmins($superAdmins);
            $this->wipePublicMedia();
            Artisan::call('cache:clear');
            Artisan::call('view:clear');
        } catch (Throwable $exception) {
            report($exception);
            $this->components->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->components->info('Local wipe completed.');
        $this->line('  users (super-admin): '.User::query()->count());
        $this->line('  products: '.(Schema::hasTable('products') ? (string) DB::table('products')->count() : 'n/a'));
        $this->line('  categories: '.(Schema::hasTable('categories') ? (string) DB::table('categories')->count() : 'n/a'));
        $this->line('  suppliers: '.(Schema::hasTable('suppliers') ? (string) DB::table('suppliers')->count() : 'n/a'));

        return self::SUCCESS;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function captureSuperAdmins(): array
    {
        if (! Schema::hasTable('users') || ! Schema::hasTable('roles')) {
            return [];
        }

        $roleId = Role::query()->where('slug', 'super-admin')->value('id');

        if ($roleId === null) {
            return [];
        }

        return User::query()
            ->withTrashed()
            ->where('role_id', $roleId)
            ->get()
            ->map(static function (User $user): array {
                $attrs = $user->getAttributes();
                unset($attrs['id']);
                $attrs['deleted_at'] = null;

                return $attrs;
            })
            ->values()
            ->all();
    }

    private function wipeDatabaseTables(): void
    {
        $tables = $this->listWipeableTables();
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql') {
            DB::statement('SET FOREIGN_KEY_CHECKS=0');
        } else {
            Schema::disableForeignKeyConstraints();
        }

        try {
            foreach ($tables as $table) {
                // Avoid DB::table()->truncate(): database names with dots (e.g. agricart.pk)
                // are mis-quoted as schema.table by the grammar.
                if ($driver === 'mysql') {
                    $safe = str_replace('`', '``', $table);
                    DB::statement("TRUNCATE TABLE `{$safe}`");
                } else {
                    DB::table($table)->delete();
                }
            }
        } finally {
            if ($driver === 'mysql') {
                DB::statement('SET FOREIGN_KEY_CHECKS=1');
            } else {
                Schema::enableForeignKeyConstraints();
            }
        }
    }

    /**
     * @return list<string>
     */
    private function listWipeableTables(): array
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql') {
            $database = (string) Schema::getConnection()->getDatabaseName();
            $rows = DB::select(
                'SELECT TABLE_NAME AS name FROM information_schema.TABLES WHERE TABLE_SCHEMA = ? AND TABLE_TYPE = ?',
                [$database, 'BASE TABLE'],
            );

            $tables = array_map(static fn (object $row): string => (string) $row->name, $rows);
        } else {
            $tables = Schema::getTableListing();
        }

        return array_values(array_filter(
            $tables,
            static fn (string $table): bool => ! in_array($table, self::PRESERVED_TABLES, true),
        ));
    }

    /**
     * @param  list<array<string, mixed>>  $superAdmins
     */
    private function restoreSuperAdmins(array $superAdmins): void
    {
        $roleId = Role::query()->where('slug', 'super-admin')->value('id');

        if ($roleId === null) {
            throw new \RuntimeException('Super Admin role missing after permissions seed.');
        }

        foreach ($superAdmins as $attrs) {
            $attrs['role_id'] = $roleId;
            $attrs['deleted_at'] = null;
            $attrs['created_at'] = $attrs['created_at'] ?? now();
            $attrs['updated_at'] = $attrs['updated_at'] ?? now();

            // Insert raw so the hashed cast does not re-hash an already-hashed password.
            DB::table('users')->insert($attrs);
        }
    }

    private function wipePublicMedia(): void
    {
        $root = storage_path('app/public');

        if (! is_dir($root)) {
            return;
        }

        foreach (File::directories($root) as $directory) {
            File::deleteDirectory($directory);
            File::ensureDirectoryExists($directory);
        }

        foreach (File::files($root) as $file) {
            File::delete($file->getPathname());
        }
    }
}
