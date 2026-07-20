<?php

namespace Tests\Feature\Activity;

use App\Enums\ActivityEvent;
use App\Filament\Clusters\Settings\ActivityPage;
use App\Livewire\ActivityLogs\Detail;
use App\Livewire\ActivityLogs\ListLogs;
use App\Models\ActivityLog;
use App\Models\Brand;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Services\Activity\ActivityLogger;
use Filament\Facades\Filament;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Livewire\Livewire;
use Tests\TestCase;

class ActivityLogsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('admin'));
        ActivityLogger::flushRequestState();
    }

    public function test_auth_events_are_recorded_for_web_guard_only(): void
    {
        $user = User::factory()->create(['status' => User::STATUS_ACTIVE]);

        Event::dispatch(new Login('web', $user, false));
        Event::dispatch(new Logout('web', $user));
        Event::dispatch(new Failed('web', null, ['email' => 'bad@example.test', 'password' => 'secret']));
        Event::dispatch(new Login('customer', $user, false));

        $this->assertDatabaseHas('activity_logs', [
            'event' => ActivityEvent::Login->value,
            'user_id' => $user->id,
        ]);
        $this->assertDatabaseHas('activity_logs', [
            'event' => ActivityEvent::Logout->value,
            'user_id' => $user->id,
        ]);
        $this->assertDatabaseHas('activity_logs', [
            'event' => ActivityEvent::FailedLogin->value,
        ]);

        $failed = ActivityLog::query()->where('event', ActivityEvent::FailedLogin->value)->first();
        $this->assertNotNull($failed);
        $this->assertSame('bad@example.test', data_get($failed->properties, 'email'));
        $this->assertNull(data_get($failed->properties, 'password'));

        $this->assertSame(3, ActivityLog::query()->count());
    }

    public function test_model_mutations_are_logged_with_redacted_secrets_when_admin_authenticated(): void
    {
        $actor = User::factory()->create(['status' => User::STATUS_ACTIVE]);
        $this->actingAs($actor);

        $brand = Brand::query()->create([
            'code' => 'TB'.uniqid(),
            'name_en' => 'Test Brand',
            'name_ur' => 'ٹیسٹ',
            'slug' => 'test-brand-'.uniqid(),
            'status' => 'active',
            'image_path' => 'brands/test.png',
        ]);

        $this->assertDatabaseHas('activity_logs', [
            'event' => ActivityEvent::Created->value,
            'subject_type' => Brand::class,
            'subject_id' => $brand->id,
            'user_id' => $actor->id,
        ]);

        $target = User::factory()->create([
            'name' => 'Editable User',
            'status' => User::STATUS_ACTIVE,
            'password' => 'plain-secret-password',
        ]);

        ActivityLog::query()->delete();
        ActivityLogger::flushRequestState();

        $target->update([
            'name' => 'Renamed User',
            'password' => 'new-secret-password',
        ]);

        $log = ActivityLog::query()->where('event', ActivityEvent::Updated->value)->first();
        $this->assertNotNull($log);
        $this->assertSame('[redacted]', data_get($log->properties, 'attributes.password'));
        $this->assertSame('Renamed User', data_get($log->properties, 'attributes.name'));
    }

    public function test_follow_up_update_after_create_in_same_request_is_not_logged_twice(): void
    {
        $actor = User::factory()->create([
            'name' => 'Ahmed Khan',
            'email' => 'ahmed@example.test',
            'status' => User::STATUS_ACTIVE,
        ]);
        $this->actingAs($actor);
        ActivityLogger::flushRequestState();
        ActivityLog::query()->delete();

        $brand = Brand::query()->create([
            'code' => 'TB'.uniqid(),
            'name_en' => 'Once Brand',
            'name_ur' => 'ایک',
            'slug' => 'once-brand-'.uniqid(),
            'status' => 'active',
            'image_path' => 'brands/once.png',
        ]);

        // Same request follow-up save (like product label generation).
        $brand->update(['name_en' => 'Once Brand Updated']);

        $this->assertSame(1, ActivityLog::query()->count());
        $this->assertDatabaseHas('activity_logs', [
            'event' => ActivityEvent::Created->value,
            'subject_type' => Brand::class,
            'subject_id' => $brand->id,
        ]);
        $this->assertDatabaseMissing('activity_logs', [
            'event' => ActivityEvent::Updated->value,
            'subject_type' => Brand::class,
            'subject_id' => $brand->id,
        ]);

        $log = ActivityLog::query()->first();
        $this->assertSame('Ahmed Khan (ahmed@example.test)', $log?->actorLabel());
    }

    public function test_unauthorized_user_cannot_query_activity_logs(): void
    {
        $user = User::factory()->create(['status' => User::STATUS_ACTIVE, 'role_id' => null]);
        $this->actingAs($user);

        Livewire::test(ListLogs::class)->assertOk()->call('updateSearch', 'x')->assertForbidden();
        Livewire::test(Detail::class)->assertOk()->call('loadLog', 1)->assertForbidden();
    }

    public function test_authorized_user_can_open_activity_page_and_detail(): void
    {
        $role = $this->roleWithPermissions('Activity Viewer', [
            'settings.view',
            'settings.activity.view',
        ]);
        $viewer = User::factory()->create([
            'role_id' => $role->id,
            'status' => User::STATUS_ACTIVE,
        ]);

        $log = app(ActivityLogger::class)->login($viewer);
        $this->assertNotNull($log);

        $this->actingAs($viewer);

        $this->get(ActivityPage::getUrl())->assertOk();

        Livewire::test(ListLogs::class)
            ->assertOk()
            ->assertSee('signed in');

        Livewire::test(Detail::class)
            ->call('loadLog', $log->id)
            ->assertSet('log.id', $log->id);
    }

    public function test_activity_log_retention_prunes_old_records_only(): void
    {
        $old = ActivityLog::query()->create([
            'event' => ActivityEvent::Login,
            'description' => 'Old login',
            'created_at' => now()->subDays(91),
        ]);
        $recent = ActivityLog::query()->create([
            'event' => ActivityEvent::Login,
            'description' => 'Recent login',
            'created_at' => now()->subDays(10),
        ]);

        $this->artisan('activity-logs:prune --days=90')->assertSuccessful();

        $this->assertModelMissing($old);
        $this->assertModelExists($recent);
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
}
