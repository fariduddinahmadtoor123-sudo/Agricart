<?php

namespace App\Livewire\Staff;

use App\Models\StaffPublicHoliday;
use App\Models\User;
use App\Services\Staff\StaffLeaveService;
use App\Services\Staff\StaffPermissions;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\On;
use Livewire\Component;

class LeaveSettingsForm extends Component
{
    public int $monthly_paid_leaves = 2;

    /**
     * @var list<array{holiday_date: string, title_en: string, title_ur: string, is_paid: bool}>
     */
    public array $newHolidays = [];

    public bool $saving = false;

    public ?string $statusMessage = null;

    public string $statusType = 'success';

    public function mount(): void
    {
        $this->resetHolidayRows();
    }

    #[On('staff-leave-form-open')]
    public function openForm(): void
    {
        $this->monthly_paid_leaves = app(StaffLeaveService::class)->monthlyPaidLeaveAllowance();
        $this->resetHolidayRows();
        $this->statusMessage = null;
    }

    #[On('staff-leave-form-action')]
    public function handlePanelAction(string $action): void
    {
        match ($action) {
            'save_close' => $this->saveAndClose(),
            default => null,
        };
    }

    public function addHolidayRow(): void
    {
        $this->newHolidays[] = $this->blankHolidayRow();
    }

    public function removeHolidayRow(int $index): void
    {
        if (! isset($this->newHolidays[$index])) {
            return;
        }

        if (count($this->newHolidays) <= 1) {
            $this->newHolidays[$index] = $this->blankHolidayRow();

            return;
        }

        unset($this->newHolidays[$index]);
        $this->newHolidays = array_values($this->newHolidays);
    }

    public function deleteHoliday(int $holidayId): void
    {
        if (! $this->canManage()) {
            return;
        }

        $holiday = StaffPublicHoliday::query()->find($holidayId);

        if ($holiday === null) {
            return;
        }

        app(StaffLeaveService::class)->deletePublicHoliday($holiday);
        $this->setStatus(__('agricart.staff.leave.messages.holiday_removed'), 'success');
        $this->dispatch('staff-leave-settings-saved');
    }

    public function saveAndClose(): void
    {
        if ($this->persist()) {
            $this->dispatch('staff-leave-settings-saved');
            $this->dispatch('close-form-panel');
        }
    }

    public function render(): View
    {
        $holidays = StaffPublicHoliday::query()
            ->where('holiday_date', '>=', now()->subYear()->toDateString())
            ->orderByDesc('holiday_date')
            ->limit(40)
            ->get();

        return view('livewire.staff.leave.form', [
            'holidays' => $holidays,
            'canManage' => $this->canManage(),
        ]);
    }

    private function persist(): bool
    {
        if (! $this->canManage()) {
            return false;
        }

        $this->saving = true;
        $this->dispatch('staff-leave-form-busy', saving: true);

        try {
            app(StaffLeaveService::class)->updateMonthlyAllowance($this->monthly_paid_leaves);

            $rows = collect($this->newHolidays)
                ->filter(fn (array $row): bool => filled($row['holiday_date'] ?? null) && filled($row['title_en'] ?? null))
                ->values()
                ->all();

            if ($rows !== []) {
                app(StaffLeaveService::class)->addPublicHolidays($rows);
            }

            $this->setStatus(__('agricart.staff.leave.messages.settings_saved'), 'success');

            return true;
        } catch (ValidationException $exception) {
            $this->setStatus(collect($exception->errors())->flatten()->first() ?: __('agricart.staff.leave.messages.save_failed'), 'danger');

            return false;
        } catch (\Throwable $exception) {
            $this->setStatus($exception->getMessage(), 'danger');

            return false;
        } finally {
            $this->saving = false;
            $this->dispatch('staff-leave-form-busy', saving: false);
        }
    }

    /**
     * @return array{holiday_date: string, title_en: string, title_ur: string, is_paid: bool}
     */
    private function blankHolidayRow(): array
    {
        return [
            'holiday_date' => now()->format('Y-m-d'),
            'title_en' => '',
            'title_ur' => '',
            'is_paid' => true,
        ];
    }

    private function resetHolidayRows(): void
    {
        $this->newHolidays = [$this->blankHolidayRow()];
    }

    private function canManage(): bool
    {
        $user = auth()->user();

        return $user instanceof User && StaffPermissions::canManageLeaveSettings($user);
    }

    private function setStatus(string $message, string $type): void
    {
        $this->statusMessage = $message;
        $this->statusType = $type;
    }
}
