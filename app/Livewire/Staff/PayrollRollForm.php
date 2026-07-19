<?php

namespace App\Livewire\Staff;

use App\Models\User;
use App\Services\Staff\StaffMemberService;
use App\Services\Staff\StaffPayrollService;
use App\Services\Staff\StaffPermissions;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\On;
use Livewire\Component;

class PayrollRollForm extends Component
{
    public string $payrollMonth = '';

    public ?int $userId = null;

    /** @var list<array<string, mixed>> */
    public array $rolls = [];

    public bool $generated = false;

    #[On('staff-payroll-form-open')]
    public function openForm(?string $payrollMonth = null): void
    {
        $this->payrollMonth = filled($payrollMonth)
            ? Carbon::parse($payrollMonth . (str_contains($payrollMonth, '-') && strlen($payrollMonth) === 7 ? '-01' : ''))->format('Y-m')
            : now()->format('Y-m');

        $this->userId = null;
        $this->generateRoll();
    }

    #[On('staff-payroll-form-action')]
    public function handlePanelAction(string $action): void
    {
        match ($action) {
            'print' => $this->dispatch('staff-payroll-print'),
            'regenerate' => $this->generateRoll(),
            default => null,
        };
    }

    public function updatedPayrollMonth(): void
    {
        $this->generateRoll();
    }

    public function updatedUserId(): void
    {
        $this->generateRoll();
    }

    public function generateRoll(): void
    {
        if (! $this->canRun()) {
            $this->rolls = [];
            $this->generated = false;

            return;
        }

        $month = Carbon::parse($this->payrollMonth . '-01');
        $service = app(StaffPayrollService::class);

        $query = app(StaffMemberService::class)->rosterQuery()->orderBy('name');

        if ($this->userId) {
            $query->where('id', $this->userId);
        } elseif (! $this->canViewAll()) {
            $user = auth()->user();
            if ($user instanceof User) {
                $query->where('id', $user->id);
            }
        }

        $members = $query->limit(StaffPayrollService::ROLL_LIMIT)->get();
        $this->rolls = $service->rollsForRoster($members, $month);

        $this->generated = true;
    }

    public function render(): View
    {
        $roster = app(StaffMemberService::class)->rosterQuery()
            ->orderBy('name')
            ->limit(StaffPayrollService::ROLL_LIMIT)
            ->get(['id', 'name', 'user_number']);

        $service = app(StaffPayrollService::class);
        $month = Carbon::parse($this->payrollMonth . '-01');

        return view('livewire.staff.payroll.form', [
            'rosterOptions' => $roster,
            'monthLabel' => $month->translatedFormat('F Y'),
            'canRun' => $this->canRun(),
            'canViewAll' => $this->canViewAll(),
            'money' => fn (float $amount): string => $service->formatMoney($amount),
        ]);
    }

    private function canRun(): bool
    {
        $user = auth()->user();

        return $user instanceof User && StaffPermissions::canRunPayroll($user);
    }

    private function canViewAll(): bool
    {
        $user = auth()->user();

        return $user instanceof User && StaffPermissions::canViewAllPayroll($user);
    }
}
