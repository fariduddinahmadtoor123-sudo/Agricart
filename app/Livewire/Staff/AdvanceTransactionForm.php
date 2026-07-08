<?php

namespace App\Livewire\Staff;

use App\Models\StaffAdvanceTransaction;
use App\Models\User;
use App\Services\Staff\StaffAdvanceService;
use App\Services\Staff\StaffMemberService;
use App\Services\Staff\StaffPermissions;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\On;
use Livewire\Component;

class AdvanceTransactionForm extends Component
{
    public string $type = StaffAdvanceTransaction::TYPE_ADVANCE;

    public ?int $userId = null;

    public string $amount = '';

    public string $transaction_date = '';

    public string $notes = '';

    public bool $saving = false;

    public ?string $statusMessage = null;

    public string $statusType = 'success';

    #[On('staff-advance-form-open')]
    public function openForm(?string $defaultType = null): void
    {
        $this->resetForm();
        $this->transaction_date = now()->format('Y-m-d');

        if (filled($defaultType) && in_array($defaultType, [StaffAdvanceTransaction::TYPE_ADVANCE, StaffAdvanceTransaction::TYPE_REPAYMENT], true)) {
            $this->type = $defaultType;
        }
    }

    #[On('staff-advance-form-action')]
    public function handlePanelAction(string $action): void
    {
        match ($action) {
            'save_close' => $this->saveAndClose(),
            default => null,
        };
    }

    public function updatedUserId(): void
    {
        // re-render balance hint
    }

    public function saveAndClose(): void
    {
        if ($this->saving) {
            return;
        }

        if ($this->persist()) {
            $this->dispatch('staff-advances-saved');
            $this->dispatch('close-form-panel');
        }
    }

    public function render(): View
    {
        $service = app(StaffAdvanceService::class);
        $roster = app(StaffMemberService::class)->rosterQuery()
            ->orderBy('name')
            ->get(['id', 'name', 'user_number']);

        $balance = null;
        $owesUs = 0.0;
        $weOwe = 0.0;

        if ($this->userId) {
            $member = User::query()->find($this->userId);

            if ($member instanceof User) {
                $raw = $service->balanceForUser($member);
                $balance = $raw;
                $owesUs = $raw > 0 ? $raw : 0.0;
                $weOwe = $raw < 0 ? abs($raw) : 0.0;
            }
        }

        return view('livewire.staff.advances.form', [
            'rosterOptions' => $roster,
            'balance' => $balance,
            'owesUs' => $owesUs,
            'weOwe' => $weOwe,
            'money' => fn (float $amount): string => $service->formatMoney($amount),
        ]);
    }

    private function persist(): bool
    {
        if ($this->saving) {
            return false;
        }

        if (! $this->canManage()) {
            $this->setStatus(__('agricart.access.denied'), 'danger');

            return false;
        }

        $this->validate([
            'type' => ['required', 'in:advance,repayment'],
            'userId' => ['required', 'integer', 'exists:users,id'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'transaction_date' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $this->saving = true;
        $this->dispatch('staff-advance-form-busy', saving: true);

        try {
            $member = User::query()->findOrFail($this->userId);
            app(StaffAdvanceService::class)->record($member, [
                'type' => $this->type,
                'amount' => $this->amount,
                'transaction_date' => $this->transaction_date,
                'notes' => $this->notes,
            ]);

            $messageKey = $this->type === StaffAdvanceTransaction::TYPE_REPAYMENT
                ? 'agricart.staff.advances.messages.repayment_saved'
                : 'agricart.staff.advances.messages.advance_saved';

            $this->setStatus(__($messageKey, ['name' => $member->name]), 'success');

            return true;
        } catch (ValidationException $exception) {
            $this->setStatus(collect($exception->errors())->flatten()->first() ?: __('agricart.staff.advances.messages.save_failed'), 'danger');

            return false;
        } catch (\Throwable $exception) {
            $this->setStatus($exception->getMessage(), 'danger');

            return false;
        } finally {
            $this->saving = false;
            $this->dispatch('staff-advance-form-busy', saving: $this->saving);
        }
    }

    private function canManage(): bool
    {
        $user = auth()->user();

        return $user instanceof User && StaffPermissions::canManageAdvances($user);
    }

    private function resetForm(): void
    {
        $this->type = StaffAdvanceTransaction::TYPE_ADVANCE;
        $this->userId = null;
        $this->amount = '';
        $this->transaction_date = now()->format('Y-m-d');
        $this->notes = '';
        $this->statusMessage = null;
        $this->statusType = 'success';
    }

    private function setStatus(string $message, string $type): void
    {
        $this->statusMessage = $message;
        $this->statusType = $type;
    }
}
