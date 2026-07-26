<?php

namespace Tests\Feature\Dashboard;

use App\Livewire\Dashboard\ListStaffNotes;
use App\Models\Role;
use App\Models\StaffNote;
use App\Models\User;
use App\Services\Staff\StaffNoteService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class StaffNotesTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_forward_and_mark_done_flow(): void
    {
        $admin = $this->superAdmin();
        $mahoor = User::factory()->create([
            'role_id' => $admin->role_id,
            'status' => User::STATUS_ACTIVE,
            'name' => 'Mahoor Mehtoor',
            'email' => 'mahoor@test.test',
        ]);

        $note = app(StaffNoteService::class)->create($admin, [
            'title' => 'Pay electricity bill',
            'body' => 'Please pay the electricity bill on the 25th.',
            'assigned_to' => $mahoor->id,
            'due_on' => '2026-07-25',
        ]);

        $this->assertSame(StaffNote::STATUS_OPEN, $note->status);
        $this->assertSame($mahoor->id, $note->assigned_to);

        $done = app(StaffNoteService::class)->markDone($mahoor, $note);
        $this->assertSame(StaffNote::STATUS_DONE, $done->status);
        $this->assertSame($mahoor->id, $done->completed_by);
        $this->assertNotNull($done->completed_at);
    }

    public function test_livewire_create_edit_and_forward(): void
    {
        $admin = $this->superAdmin();
        $staff = User::factory()->create([
            'role_id' => $admin->role_id,
            'status' => User::STATUS_ACTIVE,
            'name' => 'UI Staff',
            'email' => 'ui-staff@test.test',
        ]);

        Livewire::actingAs($admin)
            ->test(ListStaffNotes::class)
            ->call('openCreateForm')
            ->assertSet('showForm', true)
            ->set('title', 'Improve UI pages')
            ->set('body', 'Make admin pages simpler.')
            ->set('assignedTo', $staff->id)
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('showForm', false)
            ->assertSee('Improve UI pages', false);

        $note = StaffNote::query()->where('title', 'Improve UI pages')->sole();

        Livewire::actingAs($staff)
            ->test(ListStaffNotes::class)
            ->assertSee('Improve UI pages', false)
            ->call('viewNote', $note->id)
            ->assertSet('viewingId', $note->id)
            ->assertSee(__('staff_notes.actions.view'), false)
            ->call('markDone', $note->id)
            ->assertHasNoErrors();

        $this->assertTrue($note->fresh()->isDone());

        Livewire::actingAs($admin)
            ->test(ListStaffNotes::class)
            ->call('reopen', $note->id)
            ->call('viewNote', $note->id)
            ->call('startForward', $note->id)
            ->set('forwardTo', $staff->id)
            ->call('confirmForward')
            ->assertHasNoErrors();

        $this->assertSame($staff->id, $note->fresh()->assigned_to);
        $this->assertTrue($note->fresh()->isOpen());
    }

    public function test_list_page_does_not_embed_compose_form(): void
    {
        $admin = $this->superAdmin();

        Livewire::actingAs($admin)
            ->test(ListStaffNotes::class)
            ->assertSet('showForm', false)
            ->assertDontSee(__('staff_notes.form.create_heading'), false)
            ->dispatch('staff-notes-open-form')
            ->assertSet('showForm', true)
            ->assertSee(__('staff_notes.form.create_heading'), false);
    }

    public function test_assignee_cannot_delete_creators_note(): void
    {
        $admin = $this->superAdmin();
        $limitedRole = Role::query()->create([
            'name' => 'Notes Staff',
            'slug' => 'notes-staff-'.uniqid(),
            'is_system' => false,
        ]);
        $staff = User::factory()->create([
            'role_id' => $limitedRole->id,
            'status' => User::STATUS_ACTIVE,
            'email' => 'assignee-notes@test.test',
        ]);

        $note = app(StaffNoteService::class)->create($admin, [
            'title' => 'Protected note',
            'assigned_to' => $staff->id,
        ]);

        Livewire::actingAs($staff)
            ->test(ListStaffNotes::class)
            ->call('deleteNote', $note->id);

        $this->assertTrue(StaffNote::query()->whereKey($note->id)->exists());
    }

    public function test_note_print_supports_a4_and_receipt_layouts(): void
    {
        $admin = $this->superAdmin();
        $note = app(StaffNoteService::class)->create($admin, [
            'title' => 'Printable note',
            'body' => 'Please call the supplier tomorrow.',
            'due_on' => '2026-07-30',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.staff-notes.print', ['note' => $note, 'autoprint' => 0]))
            ->assertOk()
            ->assertSee('Printable note', false)
            ->assertSee('paper=a4', false)
            ->assertSee('paper=receipt', false)
            ->assertSee('note-sheet--a4', false);

        $this->actingAs($admin)
            ->get(route('admin.staff-notes.print', ['note' => $note, 'paper' => 'receipt', 'autoprint' => 0]))
            ->assertOk()
            ->assertSee('note-sheet--receipt', false)
            ->assertSee('Please call the supplier tomorrow.', false);
    }

    public function test_outsider_cannot_print_staff_note(): void
    {
        $admin = $this->superAdmin();
        $limitedRole = Role::query()->create([
            'name' => 'Notes Outsider',
            'slug' => 'notes-outsider-'.uniqid(),
            'is_system' => false,
        ]);
        $outsider = User::factory()->create([
            'role_id' => $limitedRole->id,
            'status' => User::STATUS_ACTIVE,
            'email' => 'outsider-notes@test.test',
        ]);

        $note = app(StaffNoteService::class)->create($admin, [
            'title' => 'Private note',
        ]);

        $this->actingAs($outsider)
            ->get(route('admin.staff-notes.print', $note))
            ->assertForbidden();
    }

    private function superAdmin(): User
    {
        $role = Role::query()->firstOrCreate(
            ['slug' => 'super-admin'],
            ['name' => 'Super Admin', 'is_system' => true],
        );

        return User::factory()->create([
            'role_id' => $role->id,
            'status' => User::STATUS_ACTIVE,
        ]);
    }
}
