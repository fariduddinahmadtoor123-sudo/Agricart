<?php

namespace App\Livewire\Staff;

use App\Models\StaffSalaryAssignment;
use App\Models\User;
use App\Services\Staff\StaffMemberService;
use App\Services\Staff\StaffPermissions;
use App\Services\Staff\StaffSalaryService;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\On;
use Livewire\Component;

class SalaryAssignmentForm extends Component
{
    public ?int $assignmentId = null;

    public ?int $userId = null;

    public string $mode = 'create';

    public string $monthly_gross = '';

    public string $effective_from = '';

    public string $notes = '';

    public bool $saving = false;

    public ?string $statusMessage = null;

    public string $statusType = 'success';

    #[On('staff-salary-form-open')]
    public function openForm(?int $userId = null, string $mode = 'create'): void
    {
        $this->resetForm();
        $this->mode = $mode;
        $this->userId = $userId;

        if ($userId) {
            $member = User::query()->find($userId);
            $assignment = $member ? app(StaffSalaryService::class)->currentForUser($member) : null;

            if ($assignment instanceof StaffSalaryAssignment) {
                $this->assignmentId = $assignment->id;
                $this->monthly_gross = (string) $assignment->monthly_gross;
                $this->effective_from = $assignment->effective_from->format('Y-m-d');
                $this->notes = (string) ($assignment->notes ?? '');
                $this->mode = 'edit';
            }
        }
    }

    #[On('staff-salary-form-action')]
    public function handlePanelAction(string $action): void
    {
        match ($action) {
            'save_close' => $this->saveAndClose(),
            'save_next' => $this->saveAndClose(),
            default => null,
        };
    }

    public function saveAndClose(): void
    {
        if ($this->persist()) {
            $this->dispatch('pay-management-saved');
            $this->dispatch('close-form-panel');
        }
    }

    public function render(): View
    {
        $roster = app(StaffMemberService::class)->rosterQuery()
            ->orderBy('name')
            ->get(['id', 'name', 'user_number']);

        return view('livewire.staff.pay-management.form', [
            'rosterOptions' => $roster,
            'readonly' => false,
        ]);
    }

    private function persist(): bool
    {
        if (! $this->canManage()) {
            $this->setStatus(__('agricart.access.denied'), 'danger');

            return false;
        }

        $this->validate([
            'userId' => ['required', 'integer', 'exists:users,id'],
            'monthly_gross' => ['required', 'numeric', 'min:1'],
            'effective_from' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $this->saving = true;
        $this->statusMessage = null;

        try {
            $member = User::query()->findOrFail($this->userId);
            $service = app(StaffSalaryService::class);
            $payload = [
                'monthly_gross' => $this->monthly_gross,
                'effective_from' => $this->effective_from,
                'notes' => $this->notes,
            ];

            if ($this->assignmentId && $this->mode === 'edit') {
                $assignment = StaffSalaryAssignment::query()->findOrFail($this->assignmentId);
                $service->updateAssignment($assignment, $payload);
                $this->setStatus(__('agricart.staff.pay_management.messages.updated', ['name' => $member->name]), 'success');
            } else {
                $service->assign($member, $payload);
                $this->setStatus(__('agricart.staff.pay_management.messages.assigned', ['name' => $member->name]), 'success');
            }

            return true;
        } catch (ValidationException $exception) {
            $message = collect($exception->errors())->flatten()->first();
            $this->setStatus($message ?: __('agricart.staff.pay_management.messages.save_failed'), 'danger');

            return false;
        } catch (\Throwable $exception) {
            $this->setStatus($exception->getMessage(), 'danger');

            return false;
        } finally {
            $this->saving = false;
            $this->dispatch('staff-salary-form-busy', saving: $this->saving);
        }
    }

    private function canManage(): bool
    {
        $user = auth()->user();

        if (! $user instanceof User) {
            return false;
        }

        return StaffPermissions::canManageRoster($user);
    }

    private function resetForm(): void
    {
        $this->reset(['assignmentId', 'userId', 'monthly_gross', 'notes', 'statusMessage']);
        $this->mode = 'create';
        $this->effective_from = now()->format('Y-m-d');
        $this->statusType = 'success';
        $this->saving = false;
    }

    private function setStatus(string $message, string $type): void
    {
        $this->statusMessage = $message;
        $this->statusType = $type;
    }
}
