<?php

namespace App\Filament\Clusters\Tools;

use App\Filament\Clusters\ToolsCluster;
use App\Filament\Pages\BaseModulePage;
use App\Models\ToolsHomeExpenseItem;
use App\Models\ToolsHomeExpenseSheet;
use App\Services\Access\ModulePageAccess;
use BackedEnum;
use Carbon\Carbon;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\On;

class HomeExpensesPage extends BaseModulePage
{
    protected static ?string $cluster = ToolsCluster::class;

    protected static ?string $navigationLabel = 'Home Expenses';

    protected static ?int $navigationSort = 7;

    protected static ?string $slug = 'home-expenses';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedHome;

    protected string $view = 'filament.pages.tools.home-expenses';

    public string $search = '';

    public bool $showAddForm = false;

    public string $expenseName = '';

    public string $expectedAmount = '';

    public string $expenseDate = '';

    public ?int $payingItemId = null;

    public string $payingItemName = '';

    public string $payAmount = '';

    public ?int $openSheetId = null;

    protected ?ToolsHomeExpenseSheet $requestActiveSheet = null;

    public function mount(): void
    {
        $this->expenseDate = $this->defaultExpenseDate();
        $this->ensureOpenSheet();
        $this->dispatchTotals();
    }

    #[On('tools-home-expenses-search')]
    public function updateSearch(string $query): void
    {
        $this->search = $query;
    }

    #[On('tools-home-expenses-action')]
    public function handleToolbarAction(string $action): void
    {
        match ($action) {
            'add-expense' => $this->openAddForm(),
            'close-sheet' => $this->closeOpenSheet(),
            default => null,
        };
    }

    public function openAddForm(): void
    {
        $this->authorizeEdit();
        $this->ensureOpenSheet();
        $this->closePayForm();
        $this->expenseName = '';
        $this->expectedAmount = '';
        $this->expenseDate = $this->defaultExpenseDate();
        $this->showAddForm = true;
    }

    public function closeAddForm(): void
    {
        $this->showAddForm = false;
        $this->expenseName = '';
        $this->expectedAmount = '';
        $this->expenseDate = $this->defaultExpenseDate();
        $this->dispatchTotals();
    }

    public function saveExpense(): void
    {
        $this->authorizeEdit();
        $sheet = $this->ensureOpenSheet();

        $name = trim($this->expenseName);
        $amount = round((float) str_replace(',', '', trim($this->expectedAmount)), 2);

        if ($name === '') {
            Notification::make()
                ->title(__('agricart.pages.tools.he_name_required'))
                ->danger()
                ->send();

            return;
        }

        if ($amount <= 0) {
            Notification::make()
                ->title(__('agricart.pages.tools.he_amount_invalid'))
                ->danger()
                ->send();

            return;
        }

        $date = $this->defaultExpenseDate();
        if (trim($this->expenseDate) !== '') {
            try {
                $date = Carbon::parse($this->expenseDate)->toDateString();
            } catch (\Throwable) {
                Notification::make()
                    ->title(__('agricart.pages.tools.he_date_invalid'))
                    ->danger()
                    ->send();

                return;
            }
        }

        ToolsHomeExpenseItem::query()->create([
            'sheet_id' => $sheet->id,
            'user_id' => (int) auth()->id(),
            'name' => mb_substr($name, 0, 160),
            'expected_amount' => $amount,
            'expense_date' => $date,
            'is_paid' => false,
            'paid_amount' => null,
            'paid_at' => null,
        ]);

        $this->showAddForm = false;
        $this->expenseName = '';
        $this->expectedAmount = '';
        $this->expenseDate = $this->defaultExpenseDate();
        $this->dispatchTotals();

        Notification::make()
            ->title(__('agricart.pages.tools.he_saved'))
            ->success()
            ->send();
    }

    public function openPayForm(int $itemId): void
    {
        $this->authorizeEdit();
        $sheet = $this->ensureOpenSheet();

        $item = ToolsHomeExpenseItem::query()
            ->where('user_id', auth()->id())
            ->where('sheet_id', $sheet->id)
            ->findOrFail($itemId);

        if ($item->is_paid) {
            return;
        }

        $this->showAddForm = false;
        $this->payingItemId = $item->id;
        $this->payingItemName = (string) $item->name;
        $this->payAmount = (string) (int) round((float) $item->expected_amount);
    }

    public function closePayForm(): void
    {
        $this->payingItemId = null;
        $this->payingItemName = '';
        $this->payAmount = '';
    }

    public function confirmPay(): void
    {
        if (! $this->payingItemId) {
            return;
        }

        $this->authorizeEdit();
        $sheet = $this->ensureOpenSheet();

        $item = ToolsHomeExpenseItem::query()
            ->where('user_id', auth()->id())
            ->where('sheet_id', $sheet->id)
            ->findOrFail($this->payingItemId);

        if ($item->is_paid) {
            $this->closePayForm();

            return;
        }

        $amount = round((float) str_replace(',', '', trim($this->payAmount)), 2);

        if ($amount <= 0) {
            Notification::make()
                ->title(__('agricart.pages.tools.he_pay_amount_invalid'))
                ->danger()
                ->send();

            return;
        }

        $item->is_paid = true;
        $item->paid_amount = $amount;
        $item->paid_at = now();
        $item->save();

        $this->closePayForm();
        $this->dispatchTotals();

        Notification::make()
            ->title(__('agricart.pages.tools.he_marked_paid'))
            ->success()
            ->send();
    }

    public function addAgain(int $itemId): void
    {
        $this->authorizeEdit();
        $sheet = $this->ensureOpenSheet();

        $item = ToolsHomeExpenseItem::query()
            ->where('user_id', auth()->id())
            ->where('sheet_id', $sheet->id)
            ->findOrFail($itemId);

        ToolsHomeExpenseItem::query()->create([
            'sheet_id' => $sheet->id,
            'user_id' => (int) auth()->id(),
            'name' => $item->name,
            'expected_amount' => $item->expected_amount,
            'expense_date' => $this->defaultExpenseDate(),
            'is_paid' => false,
            'paid_amount' => null,
            'paid_at' => null,
        ]);

        $this->dispatchTotals();

        Notification::make()
            ->title(__('agricart.pages.tools.he_added_again'))
            ->success()
            ->send();
    }

    public function unmarkPaid(int $itemId): void
    {
        $this->authorizeEdit();
        $sheet = $this->ensureOpenSheet();

        $item = ToolsHomeExpenseItem::query()
            ->where('user_id', auth()->id())
            ->where('sheet_id', $sheet->id)
            ->findOrFail($itemId);

        if (! $item->is_paid) {
            return;
        }

        $item->is_paid = false;
        $item->paid_amount = null;
        $item->paid_at = null;
        $item->save();
        $this->dispatchTotals();
    }

    public function deleteExpense(int $itemId): void
    {
        $this->authorizeEdit();
        $sheet = $this->ensureOpenSheet();

        ToolsHomeExpenseItem::query()
            ->where('user_id', auth()->id())
            ->where('sheet_id', $sheet->id)
            ->where('id', $itemId)
            ->delete();

        $this->dispatchTotals();
    }

    public function closeOpenSheet(): void
    {
        $this->authorizeEdit();

        $sheet = ToolsHomeExpenseSheet::query()
            ->where('user_id', auth()->id())
            ->where('status', 'open')
            ->first();

        if (! $sheet) {
            return;
        }

        if ($sheet->items()->count() === 0) {
            Notification::make()
                ->title(__('agricart.pages.tools.he_close_empty'))
                ->warning()
                ->send();

            return;
        }

        $period = $this->sheetPeriodStart($sheet)->addMonthNoOverflow()->startOfMonth();
        $newSheet = $this->rollSheetForward($sheet, $period);

        $this->openSheetId = $newSheet->id;
        $this->requestActiveSheet = $newSheet;
        $this->expenseDate = $period->toDateString();
        $this->dispatchTotals();

        Notification::make()
            ->title(__('agricart.pages.tools.he_sheet_closed'))
            ->body(__('agricart.pages.tools.he_sheet_closed_body', [
                'count' => $newSheet->items()->count(),
            ]))
            ->success()
            ->send();
    }

    /**
     * @return Collection<int, ToolsHomeExpenseItem>
     */
    public function listedItems(): Collection
    {
        $sheet = $this->ensureOpenSheet();

        $query = ToolsHomeExpenseItem::query()
            ->where('sheet_id', $sheet->id)
            ->where('user_id', auth()->id())
            // Unpaid first (to pay); paid sink to the end.
            ->orderBy('is_paid')
            ->orderByDesc('id');

        $search = trim($this->search);

        if ($search !== '') {
            $query->where('name', 'like', '%'.$search.'%');
        }

        return $query->limit(300)->get();
    }

    /**
     * Full-sheet rows for toolbar totals (ignore search filter).
     *
     * @return Collection<int, ToolsHomeExpenseItem>
     */
    public function sheetItemsForTotals(): Collection
    {
        $sheet = $this->ensureOpenSheet();

        return ToolsHomeExpenseItem::query()
            ->where('sheet_id', $sheet->id)
            ->where('user_id', auth()->id())
            ->orderBy('is_paid')
            ->orderByDesc('id')
            ->limit(500)
            ->get();
    }

    /**
     * Paid rows for the summary strip above the list.
     *
     * @return Collection<int, ToolsHomeExpenseItem>
     */
    public function paidItems(): Collection
    {
        $sheet = $this->ensureOpenSheet();

        return ToolsHomeExpenseItem::query()
            ->where('sheet_id', $sheet->id)
            ->where('user_id', auth()->id())
            ->where('is_paid', true)
            ->orderByDesc('paid_at')
            ->orderByDesc('id')
            ->limit(100)
            ->get();
    }

    /**
     * @return array{expected: float, paid: float, unpaid: float, count: int, paid_count: int}
     */
    public function sheetTotals(?Collection $items = null): array
    {
        $items ??= $this->listedItems();

        $expected = 0.0;
        $paid = 0.0;
        $remaining = 0.0;
        $paidCount = 0;

        foreach ($items as $item) {
            $expected += (float) $item->expected_amount;
            if ($item->is_paid) {
                $paid += (float) ($item->paid_amount ?? $item->expected_amount);
                $paidCount++;
            } else {
                $remaining += (float) $item->expected_amount;
            }
        }

        return [
            'expected' => round($expected, 2),
            'paid' => round($paid, 2),
            'unpaid' => round($remaining, 2),
            'count' => $items->count(),
            'paid_count' => $paidCount,
        ];
    }

    public function activeSheetTitle(): string
    {
        return (string) ($this->ensureOpenSheet()->title ?? '');
    }

    protected function defaultExpenseDate(): string
    {
        return now()->startOfMonth()->toDateString();
    }

    protected function currentPeriodYm(): string
    {
        return now()->format('Y-m');
    }

    protected function sheetPeriodStart(ToolsHomeExpenseSheet $sheet): Carbon
    {
        $ym = $sheet->period_ym ?: ($sheet->created_at?->format('Y-m') ?: $this->currentPeriodYm());

        return Carbon::createFromFormat('Y-m', $ym)->startOfMonth();
    }

    protected function ensureOpenSheet(): ToolsHomeExpenseSheet
    {
        if (
            $this->requestActiveSheet instanceof ToolsHomeExpenseSheet
            && $this->requestActiveSheet->status === 'open'
            && ($this->requestActiveSheet->period_ym ?: '') >= $this->currentPeriodYm()
        ) {
            return $this->requestActiveSheet;
        }

        $userId = (int) auth()->id();
        $sheet = null;

        if ($this->openSheetId) {
            $sheet = ToolsHomeExpenseSheet::query()
                ->where('user_id', $userId)
                ->where('id', $this->openSheetId)
                ->where('status', 'open')
                ->first();
        }

        if (! $sheet) {
            $sheet = ToolsHomeExpenseSheet::query()
                ->where('user_id', $userId)
                ->where('status', 'open')
                ->latest('id')
                ->first();
        }

        if (! $sheet) {
            $sheet = $this->createSheetForPeriod(now()->startOfMonth(), $userId);
        } else {
            // Auto-save previous months and open a fresh unpaid list for the current month.
            $guard = 0;
            while (($sheet->period_ym ?: '') < $this->currentPeriodYm() && $guard < 24) {
                $next = $this->sheetPeriodStart($sheet)->addMonthNoOverflow()->startOfMonth();
                $sheet = $this->rollSheetForward($sheet, $next);
                $guard++;
            }
        }

        $this->openSheetId = $sheet->id;
        $this->requestActiveSheet = $sheet;

        return $sheet;
    }

    protected function createSheetForPeriod(Carbon $periodStart, ?int $userId = null): ToolsHomeExpenseSheet
    {
        $userId ??= (int) auth()->id();

        return ToolsHomeExpenseSheet::query()->create([
            'user_id' => $userId,
            'title' => $periodStart->translatedFormat('F Y'),
            'period_ym' => $periodStart->format('Y-m'),
            'status' => 'open',
            'closed_at' => null,
        ]);
    }

    /**
     * Close $sheet (saved month) and seed next period with one unpaid row per expense name.
     */
    protected function rollSheetForward(ToolsHomeExpenseSheet $sheet, Carbon $nextPeriod): ToolsHomeExpenseSheet
    {
        return DB::transaction(function () use ($sheet, $nextPeriod) {
            /** @var ToolsHomeExpenseSheet $locked */
            $locked = ToolsHomeExpenseSheet::query()
                ->whereKey($sheet->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($locked->status !== 'open') {
                $open = ToolsHomeExpenseSheet::query()
                    ->where('user_id', auth()->id())
                    ->where('status', 'open')
                    ->latest('id')
                    ->first();

                return $open ?? $this->createSheetForPeriod($nextPeriod);
            }

            $items = ToolsHomeExpenseItem::query()
                ->where('sheet_id', $locked->id)
                ->where('user_id', auth()->id())
                ->orderBy('id')
                ->get();

            // One template per name (grocery ×3 → one next-month unpaid row).
            $seedItems = $items
                ->reverse()
                ->unique(fn (ToolsHomeExpenseItem $item) => mb_strtolower(trim((string) $item->name)))
                ->reverse()
                ->values();

            $locked->status = 'closed';
            $locked->closed_at = now();
            $locked->save();

            $newSheet = $this->createSheetForPeriod($nextPeriod);

            foreach ($seedItems as $seed) {
                ToolsHomeExpenseItem::query()->create([
                    'sheet_id' => $newSheet->id,
                    'user_id' => (int) auth()->id(),
                    'name' => $seed->name,
                    'expected_amount' => $seed->expected_amount,
                    'expense_date' => $nextPeriod->toDateString(),
                    'is_paid' => false,
                    'paid_amount' => null,
                    'paid_at' => null,
                ]);
            }

            return $newSheet->fresh() ?? $newSheet;
        });
    }

    protected function dispatchTotals(): void
    {
        $items = $this->sheetItemsForTotals();
        $totals = $this->sheetTotals($items);
        $money = static fn ($n) => 'Rs '.number_format((float) $n, 0, '.', ',');

        $payload = [
            'title' => $this->activeSheetTitle(),
            'expected' => $money($totals['expected']),
            'paid' => $money($totals['paid']),
            'remaining' => $money($totals['unpaid']),
            'viewingClosed' => false,
        ];

        $this->dispatch(
            'tools-home-expenses-totals',
            title: $payload['title'],
            expected: $payload['expected'],
            paid: $payload['paid'],
            remaining: $payload['remaining'],
            viewingClosed: $payload['viewingClosed'],
        );
        $this->js(
            'window.dispatchEvent(new CustomEvent("tools-home-expenses-totals",{detail:'.json_encode($payload, JSON_UNESCAPED_UNICODE).'}))'
        );
    }

    protected function authorizeEdit(): void
    {
        $user = auth()->user();

        abort_unless(
            $user && (
                $user->isSuperAdmin()
                || ModulePageAccess::can($user, 'tools', 'home-expenses', 'edit')
                || ModulePageAccess::can($user, 'tools', 'home-expenses', 'create')
            ),
            403
        );
    }
}
