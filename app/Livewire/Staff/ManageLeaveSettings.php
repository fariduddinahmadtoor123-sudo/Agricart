<?php

namespace App\Livewire\Staff;

use App\Models\StaffPublicHoliday;
use App\Models\User;
use App\Services\Staff\StaffLeaveService;
use App\Services\Staff\StaffPermissions;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class ManageLeaveSettings extends Component
{
    public int $monthly_paid_leaves = 2;

    public string $holiday_date = '';

    public string $title_en = '';

    public string $title_ur = '';

    public bool $holiday_is_paid = true;

    public string $holiday_notes = '';

    public ?string $statusMessage = null;

    public string $statusType = 'success';

    public function mount(): void
    {
        $this->monthly_paid_leaves = app(StaffLeaveService::class)->monthlyPaidLeaveAllowance();
        $this->holiday_date = now()->format('Y-m-d');
    }

    public function saveAllowance(): void
    {
        if (! $this->canManage()) {
            return;
        }

        try {
            app(StaffLeaveService::class)->updateMonthlyAllowance($this->monthly_paid_leaves);
            $this->flash(__('agricart.staff.leave.messages.allowance_saved'), 'success');
        } catch (ValidationException $exception) {
            $this->flash(collect($exception->errors())->flatten()->first() ?: __('agricart.staff.leave.messages.save_failed'), 'error');
        }
    }

    public function addHoliday(): void
    {
        if (! $this->canManage()) {
            return;
        }

        $this->validate([
            'holiday_date' => ['required', 'date'],
            'title_en' => ['required', 'string', 'max:255'],
            'title_ur' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            app(StaffLeaveService::class)->addPublicHoliday([
                'holiday_date' => $this->holiday_date,
                'title_en' => $this->title_en,
                'title_ur' => $this->title_ur,
                'is_paid' => $this->holiday_is_paid,
                'notes' => $this->holiday_notes,
            ]);

            $this->title_en = '';
            $this->title_ur = '';
            $this->holiday_notes = '';
            $this->flash(__('agricart.staff.leave.messages.holiday_added'), 'success');
        } catch (\Throwable $exception) {
            $this->flash($exception->getMessage(), 'error');
        }
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
        $this->flash(__('agricart.staff.leave.messages.holiday_removed'), 'success');
    }

    public function render(): View
    {
        $holidays = StaffPublicHoliday::query()
            ->where('holiday_date', '>=', now()->subMonths(3)->toDateString())
            ->orderBy('holiday_date')
            ->get();

        return view('livewire.staff.leave.manage', [
            'holidays' => $holidays,
            'canManage' => $this->canManage(),
        ]);
    }

    private function canManage(): bool
    {
        $user = auth()->user();

        return $user instanceof User && StaffPermissions::canManageLeaveSettings($user);
    }

    private function flash(string $message, string $type): void
    {
        $this->statusMessage = $message;
        $this->statusType = $type;
    }
}
