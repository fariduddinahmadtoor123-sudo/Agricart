<?php

namespace Tests\Feature\SystemErrors;

use App\Filament\Clusters\Settings\SystemErrorsPage;
use App\Filament\Clusters\SettingsCluster;
use App\Livewire\SystemErrors\Detail;
use App\Livewire\SystemErrors\ListErrors;
use App\Models\Permission;
use App\Models\Role;
use App\Models\SystemError;
use App\Models\User;
use App\Services\Notifications\TopbarNotificationService;
use App\Services\SystemErrors\SystemErrorCaptureService;
use App\Services\SystemErrors\SystemErrorLifecycleService;
use App\Services\SystemErrors\SystemErrorReadService;
use App\Services\SystemErrors\SystemErrorReportFormatter;
use App\Services\SystemErrors\SystemErrorSanitizer;
use App\Services\SystemErrors\SystemLogFileReader;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use RuntimeException;
use Tests\TestCase;

class SystemErrorInboxTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    public function test_capture_redacts_secrets_and_groups_repeated_errors(): void
    {
        $capture = app(SystemErrorCaptureService::class);

        $first = $capture->captureNow($this->repeatableException());

        $this->assertInstanceOf(SystemError::class, $first);
        $this->assertStringNotContainsString('secret-value', $first->message);
        $this->assertStringNotContainsString('abc123token', $first->message);
        $this->assertStringNotContainsString('customer@example.com', $first->message);
        $this->assertStringContainsString('[REDACTED]', $first->message);
        $sqlMessage = app(SystemErrorSanitizer::class)
            ->message("SQLSTATE[23000]: insert into users (email) values ('private@example.com')");
        $this->assertStringNotContainsString('private@example.com', (string) $sqlMessage);
        $this->assertStringContainsString("'[VALUE]'", (string) $sqlMessage);

        $first->update([
            'status' => SystemError::STATUS_RESOLVED,
            'resolved_at' => now(),
        ]);

        $second = $capture->captureNow($this->repeatableException());

        $this->assertSame($first->id, $second?->id);
        $this->assertSame(2, $second?->occurrences);
        $this->assertSame(SystemError::STATUS_OPEN, $second?->status);
        $this->assertNull($second?->resolved_at);
        $this->assertDatabaseCount('system_errors', 1);
    }

    public function test_laravel_report_pipeline_writes_to_the_secure_inbox(): void
    {
        report(new RuntimeException('Global pipeline test exception'));

        $this->assertDatabaseHas('system_errors', [
            'exception_class' => RuntimeException::class,
            'message' => 'Global pipeline test exception',
            'status' => SystemError::STATUS_OPEN,
        ]);
    }

    public function test_routine_validation_errors_and_missing_storage_are_ignored_safely(): void
    {
        $capture = app(SystemErrorCaptureService::class);

        $this->assertNull($capture->captureNow(ValidationException::withMessages(['name' => 'Required'])));
        $this->assertNull($capture->captureNow(new ConnectionException('OpenRouter timed out.')));

        Schema::drop('system_errors');

        $this->assertNull($capture->captureNow(new RuntimeException('Database-backed inbox is unavailable.')));
    }

    public function test_authorized_user_can_view_and_manager_can_resolve_errors(): void
    {
        $viewRole = $this->roleWithPermissions('Error Viewer', [
            'settings.view',
            'settings.system-errors.view',
        ]);
        $viewer = User::factory()->create([
            'role_id' => $viewRole->id,
            'status' => User::STATUS_ACTIVE,
        ]);
        $error = $this->systemError();

        $this->actingAs($viewer);

        $this->assertTrue($viewer->hasPermission('settings.view'));
        $this->assertTrue($viewer->hasPermission('settings.system-errors.view'));
        $this->assertTrue(SettingsCluster::canAccess());
        $this->assertTrue(SystemErrorsPage::canAccess());
        $this->get(SystemErrorsPage::getUrl())->assertOk();
        Livewire::test(ListErrors::class)
            ->assertSee($error->message);
        Livewire::test(Detail::class)
            ->call('loadError', $error->id)
            ->call('resolve')
            ->assertForbidden();

        $manageRole = $this->roleWithPermissions('Error Manager', [
            'settings.view',
            'settings.system-errors.view',
            'settings.system-errors.manage',
        ]);
        $manager = User::factory()->create([
            'role_id' => $manageRole->id,
            'status' => User::STATUS_ACTIVE,
        ]);

        $this->actingAs($manager);

        Livewire::test(Detail::class)
            ->call('loadError', $error->id)
            ->call('resolve')
            ->assertHasNoErrors();

        $this->assertSame(SystemError::STATUS_RESOLVED, $error->refresh()->status);
        $this->assertSame($manager->id, $error->resolved_by_id);
    }

    public function test_technical_report_and_file_fallback_are_sanitized(): void
    {
        $sanitizer = app(SystemErrorSanitizer::class);
        $error = $this->systemError([
            'message' => $sanitizer->message('authorization=BearerSecret password=secret-value'),
            'trace' => $sanitizer->trace('token=abc123token customer@example.com'),
        ]);
        $report = app(SystemErrorReportFormatter::class)->format($error);

        $this->assertStringNotContainsString('secret-value', $report);
        $this->assertStringNotContainsString('abc123token', $report);
        $this->assertStringNotContainsString('customer@example.com', $report);

        $path = storage_path('logs/laravel-2099-01-01.log');

        try {
            File::put(
                $path,
                "[2099-01-01 12:00:00] production.ERROR: password=secret-value token=abc123token customer@example.com\n#0 /private/path.php(10)\n",
            );
            touch($path, time() + 10);

            $entry = collect(app(SystemLogFileReader::class)->recent(200))
                ->first(fn (array $item): bool => $item['file'] === 'laravel-2099-01-01.log');

            $this->assertIsArray($entry);
            $this->assertStringNotContainsString('secret-value', $entry['trace']);
            $this->assertStringNotContainsString('abc123token', $entry['trace']);
            $this->assertStringNotContainsString('customer@example.com', $entry['trace']);
        } finally {
            File::delete($path);
        }
    }

    public function test_resolved_error_retention_and_critical_bell_count(): void
    {
        $this->actingAs($this->superAdmin());

        $oldResolved = $this->systemError([
            'fingerprint' => hash('sha256', 'old-resolved'),
            'status' => SystemError::STATUS_RESOLVED,
            'severity' => SystemError::SEVERITY_ERROR,
            'resolved_at' => now()->subDays(91),
        ]);
        $openCritical = $this->systemError([
            'fingerprint' => hash('sha256', 'open-critical'),
            'status' => SystemError::STATUS_OPEN,
            'severity' => SystemError::SEVERITY_CRITICAL,
        ]);

        $this->artisan('system-errors:prune')->assertSuccessful();

        $this->assertModelMissing($oldResolved);
        $this->assertModelExists($openCritical);

        $summary = app(TopbarNotificationService::class)->summary();
        $item = collect($summary['items'])->firstWhere('key', 'system_errors');

        $this->assertSame(1, $item['count'] ?? null);

        app(SystemErrorReadService::class)->markCurrentCriticalErrorsRead(auth()->user());
        $readSummary = app(TopbarNotificationService::class)->summary();
        $this->assertNull(collect($readSummary['items'])->firstWhere('key', 'system_errors'));

        $openCritical->increment('occurrences');
        $recurrenceSummary = app(TopbarNotificationService::class)->summary();
        $this->assertSame(
            1,
            collect($recurrenceSummary['items'])->firstWhere('key', 'system_errors')['count'] ?? null,
        );
    }

    public function test_ignored_connection_errors_have_no_historical_reconciliation_branch(): void
    {
        $error = $this->systemError([
            'exception_class' => ConnectionException::class,
            'last_seen_at' => now()->subMinutes(31),
        ]);

        $resolved = app(SystemErrorLifecycleService::class)->reconcile();

        $this->assertSame(0, $resolved);
        $this->assertSame(SystemError::STATUS_OPEN, $error->refresh()->status);
        $this->assertNull($error->resolution_type);
    }

    private function repeatableException(): RuntimeException
    {
        return new RuntimeException('password=secret-value token=abc123token customer@example.com order 12345678901');
    }

    /**
     * @param  list<string>  $permissions
     */
    private function roleWithPermissions(string $name, array $permissions): Role
    {
        $role = Role::query()->create([
            'name' => $name,
            'slug' => str($name)->slug()->toString(),
            'is_system' => false,
        ]);
        $permissionIds = collect($permissions)->map(function (string $key): int {
            return Permission::query()->firstOrCreate(
                ['key' => $key],
                [
                    'group_key' => 'settings',
                    'label_en' => $key,
                    'label_ur' => $key,
                    'sort_order' => 1,
                ],
            )->id;
        });
        $role->permissions()->attach($permissionIds);

        return $role;
    }

    private function superAdmin(): User
    {
        $role = Role::query()->create([
            'name' => 'Super Admin',
            'slug' => 'super-admin',
            'is_system' => true,
        ]);

        return User::factory()->create([
            'role_id' => $role->id,
            'status' => User::STATUS_ACTIVE,
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function systemError(array $overrides = []): SystemError
    {
        $now = now();

        return SystemError::query()->create([
            'reference' => (string) str()->ulid(),
            'fingerprint' => hash('sha256', uniqid('system-error', true)),
            'source' => 'exception',
            'severity' => SystemError::SEVERITY_ERROR,
            'status' => SystemError::STATUS_OPEN,
            'exception_class' => RuntimeException::class,
            'message' => 'Sanitized test exception',
            'file' => 'app/Test.php',
            'line' => 42,
            'trace' => '#0 sanitized trace',
            'occurrences' => 1,
            'first_seen_at' => $now,
            'last_seen_at' => $now,
            ...$overrides,
        ]);
    }
}
