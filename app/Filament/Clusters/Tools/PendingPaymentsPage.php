<?php

namespace App\Filament\Clusters\Tools;

use App\Filament\Clusters\ToolsCluster;
use App\Filament\Pages\BaseModulePage;
use App\Livewire\Tools\ReOrderSettingsForm;
use App\Models\ToolsPendingPaymentSheet;
use App\Services\Access\ModulePageAccess;
use App\Services\PricingControls\SecretCodingEngine;
use App\Services\Tools\PendingPaymentsSheetService;
use App\Services\Tools\PurchaseSummaryPaymentSync;
use App\Services\Tools\PurchaseSummarySettingsService;
use App\Services\Tools\ReOrderSettingsService;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use Livewire\Attributes\On;

class PendingPaymentsPage extends BaseModulePage
{
    protected static ?string $cluster = ToolsCluster::class;

    protected static ?string $navigationLabel = 'Pending Payments';

    protected static ?int $navigationSort = 4;

    protected static ?string $slug = 'pending-payments';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClock;

    protected string $view = 'filament.pages.tools.pending-payments';

    public string $screen = 'list';

    public string $formLang = 'en';

    public string $sheetNo = '';

    public string $sheetDate = '';

    public string $preparedBy = '';

    public string $entryShopkeeper = '';

    public string $entryOwed = '';

    public string $forwardTo = '';

    public string $payDate = '';

    public string $payAmount = '';

    public string $payRowKey = '';

    /**
     * @var array<string, array{shopkeeper: string, owed: string, payments: list<array{date: string, amount: string}>, status: string}|string>
     */
    public array $cells = [];

    public ?int $editingSheetId = null;

    public int $pageCount = 1;

    public const ROWS_PAGE1 = 20;

    public const ROWS_CONT = 22;

    public const GRID_COLS = 1;

    public string $draftFlash = '';

    public int $shopkeepersTick = 0;

    public string $search = '';

    public string $persistStatus = 'draft';

    public string $formMode = 'edit';

    #[On('tools-pending-payments-search')]
    public function updateSearch(string $query): void
    {
        $this->search = $query;
    }

    public function isViewMode(): bool
    {
        return $this->formMode === 'view' || $this->persistStatus === 'closed';
    }

    #[On('tools-pending-payments-action')]
    public function handleToolbarAction(string $action): void
    {
        if ($action === 'new-sheet') {
            $this->startNewSheet();

            return;
        }

        if ($this->screen !== 'form') {
            return;
        }

        if ($this->isViewMode() && in_array($action, ['add-page', 'remove-page', 'save-close', 'clear-all'], true)) {
            return;
        }

        match ($action) {
            'lang-en' => $this->setFormLang('en'),
            'lang-ur' => $this->setFormLang('ur'),
            'add-page' => $this->addSheetPage(),
            'remove-page' => $this->removeSheetPage(),
            'save-close' => $this->saveAndClose(),
            'close-form' => $this->closeSheetForm(),
            'clear-all' => $this->clearAllSheet(),
            default => null,
        };
    }

    public function startNewSheet(): void
    {
        $this->authorizeCreate();

        if ($this->screen === 'form' && ! $this->isViewMode() && $this->draftHasContent()) {
            $this->autoSaveDraft(skipRender: false);
        }

        $draft = ToolsPendingPaymentSheet::query()
            ->where('user_id', auth()->id())
            ->where('status', 'draft')
            ->latest('updated_at')
            ->first();

        if ($draft) {
            $this->applySheetRecord($draft);
            $this->persistStatus = 'draft';
            $this->formMode = 'edit';
            $this->screen = 'form';
            $this->draftFlash = 'recovered';
            $this->emitSheetScreen('form');
            Notification::make()->title(__('agricart.pages.tools.pp_draft_recovered'))->success()->send();

            return;
        }

        $this->resetFreshSheet();
        $this->persistStatus = 'draft';
        $this->formMode = 'edit';
        $this->screen = 'form';
        $this->draftFlash = '';
        $this->emitSheetScreen('form');
    }

    public function clearAllSheet(): void
    {
        if ($this->isViewMode()) {
            return;
        }
        $this->authorizeCreate();
        ToolsPendingPaymentSheet::query()
            ->where('user_id', auth()->id())
            ->where('status', 'draft')
            ->delete();
        $this->resetFreshSheet();
        $this->persistStatus = 'draft';
        $this->formMode = 'edit';
        $this->screen = 'form';
        $this->shopkeepersTick++;
        $this->emitSheetScreen('form');
        Notification::make()->title(__('agricart.pages.tools.pp_cleared'))->success()->send();
    }

    public function openSheet(int $id): void
    {
        $sheet = ToolsPendingPaymentSheet::query()
            ->where('user_id', auth()->id())
            ->whereKey($id)
            ->firstOrFail();
        $this->loadSheetIntoForm($id, $sheet->status === 'closed' ? 'view' : 'view');
    }

    public function editSheet(int $id): void
    {
        $this->authorizeCreate();
        $sheet = ToolsPendingPaymentSheet::query()
            ->where('user_id', auth()->id())
            ->whereKey($id)
            ->where('status', 'saved')
            ->firstOrFail();
        $this->loadSheetIntoForm($id, 'edit');
    }

    public function closeListedSheet(int $id): void
    {
        $this->authorizeCreate();
        $sheet = ToolsPendingPaymentSheet::query()
            ->where('user_id', auth()->id())
            ->whereKey($id)
            ->where('status', 'saved')
            ->firstOrFail();
        $sheet->update(['status' => 'closed']);
        Notification::make()->title(__('agricart.pages.tools.pp_closed_ok'))->success()->send();
    }

    public function closeSheetForm(): void
    {
        if ($this->screen === 'form' && ! $this->isViewMode() && $this->draftHasContent()) {
            $this->autoSaveDraft(skipRender: false);
        }
        $this->screen = 'list';
        $this->emitSheetScreen('list');
    }

    protected function loadSheetIntoForm(int $id, string $mode): void
    {
        $sheet = ToolsPendingPaymentSheet::query()
            ->where('user_id', auth()->id())
            ->whereKey($id)
            ->firstOrFail();
        $this->applySheetRecord($sheet);
        $status = (string) $sheet->status;
        $this->persistStatus = in_array($status, ['saved', 'draft', 'closed'], true) ? $status : 'draft';
        $this->formMode = $mode === 'view' || $status === 'closed' ? 'view' : 'edit';
        $this->screen = 'form';
        $this->shopkeepersTick++;
        $this->emitSheetScreen('form');
    }

    /**
     * @return \Illuminate\Support\Collection<int, ToolsPendingPaymentSheet>
     */
    public function listedSheets()
    {
        $query = ToolsPendingPaymentSheet::query()
            ->where('user_id', auth()->id())
            ->whereIn('status', ['saved', 'closed'])
            ->latest('updated_at');
        $term = trim($this->search);
        if ($term !== '') {
            $like = '%'.$term.'%';
            $query->where(function ($q) use ($like): void {
                $q->where('sheet_no', 'like', $like)
                    ->orWhere('sheet_date', 'like', $like)
                    ->orWhere('prepared_by', 'like', $like);
            });
        }

        return $query->limit(150)->get();
    }

    public function autoSaveDraft(bool $skipRender = true): void
    {
        if ($this->screen !== 'form' || $this->isViewMode() || ! $this->draftHasContent()) {
            return;
        }
        $this->authorizeCreate();
        $sheetNo = trim($this->sheetNo);
        if ($sheetNo === '') {
            $sheetNo = app(PendingPaymentsSheetService::class)->peekSheetNumber();
            $this->sheetNo = $sheetNo;
        }
        $status = $this->persistStatus === 'saved' ? 'saved' : 'draft';
        $payload = $this->sheetPayload($status);
        $payload['sheet_no'] = $sheetNo;
        $sheet = ToolsPendingPaymentSheet::query()->updateOrCreate(
            ['user_id' => (int) auth()->id(), 'sheet_no' => $sheetNo],
            $payload,
        );
        $this->editingSheetId = (int) $sheet->id;
        $this->draftFlash = 'saved';
        if ($skipRender) {
            $this->skipRender();
        }
    }

    public function saveAndClose(): void
    {
        if ($this->isViewMode()) {
            return;
        }
        $this->authorizeCreate();
        $service = app(PendingPaymentsSheetService::class);
        $payload = $this->sheetPayload('saved');
        $peeked = $service->peekSheetNumber();
        $sheetNo = trim($this->sheetNo);
        if ($sheetNo === '' || $sheetNo === $peeked) {
            $sheetNo = $service->nextSheetNumber();
            $this->sheetNo = $sheetNo;
        }
        $payload['sheet_no'] = $sheetNo;
        if ($this->editingSheetId) {
            ToolsPendingPaymentSheet::query()
                ->where('user_id', auth()->id())
                ->whereKey($this->editingSheetId)
                ->firstOrFail()
                ->update($payload);
        } else {
            ToolsPendingPaymentSheet::query()->updateOrCreate(
                ['user_id' => (int) auth()->id(), 'sheet_no' => $sheetNo],
                $payload,
            );
        }
        $this->editingSheetId = null;
        $this->persistStatus = 'draft';
        $this->screen = 'list';
        $this->resetFreshSheet();
        $this->emitSheetScreen('list');
        Notification::make()->title(__('agricart.pages.tools.pp_saved'))->success()->send();
    }

    public function setFormLang(string $lang): void
    {
        if (! in_array($lang, ['en', 'ur'], true)) {
            return;
        }
        $this->formLang = $lang;
        $user = auth()->user();
        $this->preparedBy = $lang === 'ur'
            ? (string) (($user?->name_ur ?: $user?->name) ?? '')
            : (string) ($user?->name ?? '');
        $this->js(
            'window.dispatchEvent(new CustomEvent("tools-pending-payments-lang-changed",{detail:{lang:'.json_encode($lang).'}}))'
        );
    }

    public function forwardEntryFromUi(string $shopkeeper = '', string $owed = '', string $box = ''): void
    {
        if ($this->isViewMode()) {
            return;
        }
        $this->authorizeCreate();
        if (trim($shopkeeper) !== '') {
            $this->entryShopkeeper = $shopkeeper;
        }
        if (trim($owed) !== '') {
            $this->entryOwed = $owed;
        }
        if (trim($box) !== '') {
            $this->forwardTo = $box;
        }
        $this->forwardEntry();
    }

    public function forwardEntry(): void
    {
        if ($this->isViewMode()) {
            return;
        }
        $this->authorizeCreate();
        [$shopkeeper, $owedFromText] = $this->splitShopkeeperAndOwed(trim($this->entryShopkeeper));
        $owed = trim($this->entryOwed);
        if ($owed === '' && $owedFromText !== '') {
            $owed = $owedFromText;
        }
        $box = ReOrderSettingsForm::normalizeDigits(trim($this->forwardTo));
        if ($shopkeeper === '') {
            Notification::make()->title(__('agricart.pages.tools.pp_forward_need_shopkeeper'))->warning()->send();

            return;
        }
        if ($box === '' || ! ctype_digit($box)) {
            Notification::make()->title(__('agricart.pages.tools.pp_forward_need_box'))->warning()->send();

            return;
        }

        $rowKey = (string) (int) $box;
        $existingKey = $this->findRowKeyByShopkeeper($shopkeeper);
        if ($existingKey !== null && $existingKey !== $rowKey) {
            Notification::make()->title(__('agricart.pages.tools.pp_shopkeeper_exists'))->warning()->send();

            return;
        }

        $cell = $this->normalizeCellValue($this->cells[$rowKey] ?? []);
        if ($this->cellIsFilled($cell) && mb_strtolower(trim($cell['shopkeeper'])) !== mb_strtolower($shopkeeper)) {
            Notification::make()->title(__('agricart.pages.tools.pp_box_occupied'))->warning()->send();

            return;
        }

        $cell['shopkeeper'] = $shopkeeper;
        if ($owed !== '') {
            $cell['owed'] = $this->encodeAmountValue($owed);
        } elseif (! isset($cell['owed'])) {
            $cell['owed'] = '';
        }
        $cell['status'] = '';
        if (! is_array($cell['payments'] ?? null)) {
            $cell['payments'] = [];
        }
        $this->cells[$rowKey] = $cell;
        app(PurchaseSummarySettingsService::class)->rememberShopkeeper($shopkeeper);
        $this->entryShopkeeper = '';
        $this->entryOwed = '';
        $this->forwardTo = '';
        $this->shopkeepersTick++;
        $needed = $this->pageCountForFilledRows((int) $rowKey);
        if ($needed > $this->pageCount) {
            $this->pageCount = min(20, $needed);
        }
        $this->autoSaveDraft(skipRender: false);
        $this->js(
            'window.__agricartPpShopkeepers = '.json_encode(array_values($this->shopkeeperOptions()), JSON_UNESCAPED_UNICODE).';'
            .'window.dispatchEvent(new CustomEvent("tools-pending-payments-shopkeepers-updated"));'
            .'window.dispatchEvent(new CustomEvent("tools-pending-payments-entry-cleared"));'
        );
    }

    public function clearEntry(): void
    {
        $this->entryShopkeeper = '';
        $this->entryOwed = '';
        $this->forwardTo = '';
    }

    /**
     * Pull a trailing amount out of spoken/typed shopkeeper text, e.g. "Ali Store 1500".
     *
     * @return array{0: string, 1: string}
     */
    protected function splitShopkeeperAndOwed(string $text): array
    {
        $text = trim($text);
        if ($text === '') {
            return ['', ''];
        }

        $normalized = ReOrderSettingsForm::normalizeDigits($text);
        if (preg_match('/^(.*?)[\s,:-]+([0-9]+(?:\.[0-9]+)?)\s*$/u', $normalized, $m) === 1) {
            $name = trim((string) $m[1]);
            $amount = trim((string) $m[2]);
            if ($name !== '' && $amount !== '') {
                return [$name, $amount];
            }
        }

        return [$text, ''];
    }

    public function openPayForm(string $rowKey): void
    {
        if ($this->isViewMode() || ! ctype_digit($rowKey)) {
            return;
        }
        $cell = $this->normalizeCellValue($this->cells[$rowKey] ?? []);
        if (! $this->cellIsFilled($cell)) {
            return;
        }
        $this->payRowKey = $rowKey;
        $this->payDate = trim($this->sheetDate) !== ''
            ? $this->sheetDate
            : now(config('app.display_timezone', config('app.timezone')))->format('d-m-Y');
        $this->payAmount = '';
    }

    public function cancelPayForm(): void
    {
        $this->payRowKey = '';
        $this->payDate = '';
        $this->payAmount = '';
    }

    public function confirmPay(): void
    {
        if ($this->isViewMode()) {
            return;
        }
        $this->authorizeCreate();
        $rowKey = $this->payRowKey;
        if (! ctype_digit($rowKey)) {
            return;
        }
        $cell = $this->normalizeCellValue($this->cells[$rowKey] ?? []);
        if (! $this->cellIsFilled($cell)) {
            return;
        }
        $date = trim($this->payDate);
        $amount = trim($this->payAmount);
        if ($date === '' || $amount === '') {
            Notification::make()->title(__('agricart.pages.tools.pp_pay_need_fields'))->warning()->send();

            return;
        }
        $encoded = $this->encodeAmountValue($amount);
        $payments = is_array($cell['payments']) ? $cell['payments'] : [];
        $payments[] = ['date' => $date, 'amount' => $encoded];
        $cell['payments'] = $payments;
        $this->cells[$rowKey] = $cell;

        $sync = app(PurchaseSummaryPaymentSync::class)->attachPaidLine(
            (int) auth()->id(),
            $cell['shopkeeper'],
            $amount,
            $date,
            $this->formLang,
        );

        $this->cancelPayForm();
        if ($this->persistStatus === 'saved') {
            $this->persistSavedSheetCells();
        } else {
            $this->autoSaveDraft(skipRender: false);
        }

        $msg = $sync['created']
            ? __('agricart.pages.tools.pp_pay_synced_new', ['summary' => $sync['summary_no']])
            : __('agricart.pages.tools.pp_pay_synced', ['summary' => $sync['summary_no']]);
        Notification::make()->title($msg)->success()->send();
    }

    public function updated(string $name, mixed $value): void
    {
        if (preg_match('/^cells\.(\d+)\.owed$/', $name, $m) === 1) {
            $key = $m[1];
            $cell = $this->normalizeCellValue($this->cells[$key] ?? []);
            $cell['owed'] = $this->encodeAmountValue((string) $value);
            $this->cells[$key] = $cell;

            return;
        }
        if (preg_match('/^cells\.(\d+)\.shopkeeper$/', $name, $m) === 1) {
            $key = $m[1];
            $nameVal = trim((string) $value);
            $other = $this->findRowKeyByShopkeeper($nameVal);
            if ($other !== null && $other !== $key && $nameVal !== '') {
                $cell = $this->normalizeCellValue($this->cells[$key] ?? []);
                $cell['shopkeeper'] = '';
                $this->cells[$key] = $cell;
                Notification::make()->title(__('agricart.pages.tools.pp_shopkeeper_exists'))->warning()->send();

                return;
            }
            if ($nameVal !== '') {
                app(PurchaseSummarySettingsService::class)->rememberShopkeeper($nameVal);
            }
        }
    }

    /**
     * @return array{owed: float, paid: float, remaining: float, owed_display: string, paid_display: string, remaining_display: string}
     */
    public function sheetTotals(): array
    {
        $engine = app(SecretCodingEngine::class);
        $slots = $this->digitSlots();
        $owed = 0.0;
        $paid = 0.0;
        foreach ($this->cells as $key => $value) {
            if (! ctype_digit((string) $key)) {
                continue;
            }
            $row = $this->normalizeCellValue($value);
            if (! $this->cellIsFilled($row)) {
                continue;
            }
            $owed += $this->decodeAmountValue($row['owed'], $engine, $slots);
            foreach ($row['payments'] as $p) {
                $paid += $this->decodeAmountValue((string) ($p['amount'] ?? ''), $engine, $slots);
            }
        }
        $remaining = $owed - $paid;

        return [
            'owed' => $owed,
            'paid' => $paid,
            'remaining' => $remaining,
            'owed_display' => $this->displayAmount($owed, $engine, $slots),
            'paid_display' => $this->displayAmount($paid, $engine, $slots),
            'remaining_display' => $this->displayAmount($remaining, $engine, $slots),
        ];
    }

    /**
     * @return array{owed: float, paid: float, remaining: float, owed_display: string, paid_display: string, remaining_display: string, lines: int}
     */
    public function listSheetMoney(ToolsPendingPaymentSheet $sheet): array
    {
        $engine = app(SecretCodingEngine::class);
        $slots = $this->digitSlots();
        $owed = 0.0;
        $paid = 0.0;
        $lines = 0;
        $cells = is_array($sheet->cells) ? $sheet->cells : [];
        foreach ($cells as $key => $value) {
            if (! ctype_digit((string) $key)) {
                continue;
            }
            $row = $this->normalizeCellValue($value);
            if (! $this->cellIsFilled($row)) {
                continue;
            }
            $lines++;
            $owed += $this->decodeAmountValue($row['owed'], $engine, $slots);
            foreach ($row['payments'] as $p) {
                $paid += $this->decodeAmountValue((string) ($p['amount'] ?? ''), $engine, $slots);
            }
        }
        $remaining = $owed - $paid;

        return [
            'owed' => $owed,
            'paid' => $paid,
            'remaining' => $remaining,
            'lines' => $lines,
            'owed_display' => $this->displayAmount($owed, $engine, $slots),
            'paid_display' => $this->displayAmount($paid, $engine, $slots),
            'remaining_display' => $this->displayAmount($remaining, $engine, $slots),
        ];
    }

    /**
     * @return list<string>
     */
    public function shopkeeperOptions(): array
    {
        return app(PurchaseSummarySettingsService::class)->shopkeepersForUser();
    }

    public function cellRow(string|int $rowNo): array
    {
        return $this->normalizeCellValue($this->cells[(string) $rowNo] ?? []);
    }

    public function rowRemainingDisplay(array $cell): string
    {
        $engine = app(SecretCodingEngine::class);
        $slots = $this->digitSlots();
        $owed = $this->decodeAmountValue($cell['owed'] ?? '', $engine, $slots);
        $paid = 0.0;
        foreach ($cell['payments'] ?? [] as $p) {
            $paid += $this->decodeAmountValue((string) ($p['amount'] ?? ''), $engine, $slots);
        }

        return $this->displayAmount($owed - $paid, $engine, $slots);
    }

    public function paymentsLabel(array $cell): string
    {
        $parts = [];
        foreach ($cell['payments'] ?? [] as $p) {
            $d = trim((string) ($p['date'] ?? ''));
            $a = trim((string) ($p['amount'] ?? ''));
            if ($d === '' && $a === '') {
                continue;
            }
            $parts[] = trim($d.($d !== '' && $a !== '' ? ': ' : '').$a);
        }

        return implode(' · ', $parts);
    }

    public function settingsData(): array
    {
        return app(ReOrderSettingsService::class)->getForUser();
    }

    public function shopName(): string
    {
        $s = $this->settingsData();
        if ($this->formLang === 'ur') {
            return (string) (($s['shopNameUr'] ?? '') !== '' ? $s['shopNameUr'] : ($s['shopNameEn'] ?? ''));
        }

        return (string) (($s['shopNameEn'] ?? '') !== '' ? $s['shopNameEn'] : ($s['shopNameUr'] ?? ''));
    }

    public function shopAddress(): string
    {
        $s = $this->settingsData();
        if ($this->formLang === 'ur') {
            return (string) (($s['addressUr'] ?? '') !== '' ? $s['addressUr'] : ($s['addressEn'] ?? ''));
        }

        return (string) (($s['addressEn'] ?? '') !== '' ? $s['addressEn'] : ($s['addressUr'] ?? ''));
    }

    /**
     * @return list<string>
     */
    public function shopPhones(): array
    {
        $s = $this->settingsData();
        $phones = is_array($s['phones'] ?? null) ? $s['phones'] : [];
        if ($phones === [] && filled($s['phone'] ?? null)) {
            $phones = [(string) $s['phone']];
        }

        return array_values(array_filter(array_map('strval', $phones)));
    }

    public function rowsPerColForPage(int $page): int
    {
        return $page <= 1 ? self::ROWS_PAGE1 : self::ROWS_CONT;
    }

    public function pageBaseForPage(int $page): int
    {
        if ($page <= 1) {
            return 0;
        }
        $first = self::ROWS_PAGE1 * self::GRID_COLS;
        $cont = self::ROWS_CONT * self::GRID_COLS;

        return $first + (($page - 2) * $cont);
    }

    public function addSheetPage(): void
    {
        if ($this->isViewMode()) {
            return;
        }
        $this->authorizeCreate();
        $this->pageCount = min(20, max(1, $this->pageCount + 1));
    }

    public function removeSheetPage(): void
    {
        if ($this->isViewMode() || $this->pageCount <= 1) {
            return;
        }
        $this->authorizeCreate();
        $this->pageCount--;
    }

    public function mount(): void
    {
        $this->normalizeCellsInPlace();
        $this->emitSheetScreen($this->screen);
    }

    public function hydrate(): void
    {
        $this->normalizeCellsInPlace();
    }

    protected function resetFreshSheet(): void
    {
        $user = auth()->user();
        $this->editingSheetId = null;
        $this->formLang = 'en';
        $this->sheetNo = app(PendingPaymentsSheetService::class)->peekSheetNumber();
        $this->sheetDate = now(config('app.display_timezone', config('app.timezone')))->format('d-m-Y');
        $this->preparedBy = (string) ($user?->name ?? '');
        $this->entryShopkeeper = '';
        $this->entryOwed = '';
        $this->forwardTo = '';
        $this->payRowKey = '';
        $this->payDate = '';
        $this->payAmount = '';
        $this->cells = [];
        $this->pageCount = 1;
        $this->draftFlash = '';
    }

    protected function applySheetRecord(ToolsPendingPaymentSheet $sheet): void
    {
        $this->editingSheetId = (int) $sheet->id;
        $this->formLang = in_array($sheet->lang, ['en', 'ur'], true) ? (string) $sheet->lang : 'en';
        $this->sheetNo = (string) $sheet->sheet_no;
        $this->sheetDate = (string) $sheet->sheet_date;
        $this->preparedBy = (string) ($sheet->prepared_by ?? '');
        $this->entryShopkeeper = '';
        $this->entryOwed = '';
        $this->forwardTo = '';
        $this->payRowKey = '';
        $this->cells = is_array($sheet->cells) ? $sheet->cells : [];
        $this->normalizeCellsInPlace();
        $this->pageCount = max($this->pageCount, $this->pageCountForFilledRows($this->maxFilledRow()));
    }

    /**
     * @return array<string, mixed>
     */
    protected function sheetPayload(string $status): array
    {
        return [
            'user_id' => (int) auth()->id(),
            'sheet_date' => trim($this->sheetDate),
            'lang' => $this->formLang,
            'shop_name' => $this->shopName(),
            'shop_address' => $this->shopAddress(),
            'shop_phones' => $this->shopPhones(),
            'prepared_by' => trim($this->preparedBy),
            'cells' => $this->cellsPayloadForSave(),
            'status' => $status,
        ];
    }

    protected function cellsPayloadForSave(): array
    {
        $out = [];
        foreach ($this->cells as $key => $value) {
            if (str_starts_with((string) $key, '_')) {
                continue;
            }
            $row = $this->normalizeCellValue($value);
            if (! $this->cellIsFilled($row)) {
                continue;
            }
            $out[(string) $key] = $row;
        }
        $out['_pageCount'] = max(1, min(20, $this->pageCount));

        return $out;
    }

    protected function persistSavedSheetCells(): void
    {
        if (! $this->editingSheetId || $this->persistStatus !== 'saved') {
            return;
        }
        $sheet = ToolsPendingPaymentSheet::query()
            ->where('user_id', auth()->id())
            ->whereKey($this->editingSheetId)
            ->where('status', 'saved')
            ->first();
        if (! $sheet) {
            return;
        }
        $sheet->update(['cells' => $this->cellsPayloadForSave()]);
    }

    protected function normalizeCellsInPlace(): void
    {
        if ($this->cells === []) {
            return;
        }
        $out = [];
        foreach ($this->cells as $key => $value) {
            $key = (string) $key;
            if ($key === '_pageCount') {
                $this->pageCount = max(1, min(20, (int) $value));
                continue;
            }
            if (str_starts_with($key, '_')) {
                continue;
            }
            $out[$key] = $this->normalizeCellValue($value);
        }
        $this->cells = $out;
    }

    /**
     * @return array{shopkeeper: string, owed: string, payments: list<array{date: string, amount: string}>, status: string}
     */
    protected function normalizeCellValue(mixed $value): array
    {
        if (! is_array($value)) {
            return ['shopkeeper' => '', 'owed' => '', 'payments' => [], 'status' => ''];
        }
        $payments = [];
        if (is_array($value['payments'] ?? null)) {
            foreach ($value['payments'] as $p) {
                if (! is_array($p)) {
                    continue;
                }
                $payments[] = [
                    'date' => trim((string) ($p['date'] ?? '')),
                    'amount' => trim((string) ($p['amount'] ?? '')),
                ];
            }
        }

        return [
            'shopkeeper' => trim((string) ($value['shopkeeper'] ?? '')),
            'owed' => trim((string) ($value['owed'] ?? '')),
            'payments' => $payments,
            'status' => '',
        ];
    }

    /**
     * @param  array{shopkeeper: string, owed: string, payments: list<array{date: string, amount: string}>, status: string}  $cell
     */
    protected function cellIsFilled(array $cell): bool
    {
        return $cell['shopkeeper'] !== '' || $cell['owed'] !== '';
    }

    protected function findRowKeyByShopkeeper(string $shopkeeper): ?string
    {
        $needle = mb_strtolower(trim($shopkeeper));
        if ($needle === '') {
            return null;
        }
        foreach ($this->cells as $key => $value) {
            if (! ctype_digit((string) $key)) {
                continue;
            }
            $row = $this->normalizeCellValue($value);
            if (mb_strtolower(trim($row['shopkeeper'])) === $needle) {
                return (string) $key;
            }
        }

        return null;
    }

    protected function draftHasContent(): bool
    {
        return trim($this->entryShopkeeper) !== ''
            || trim($this->entryOwed) !== ''
            || $this->normalizedCellsExist();
    }

    protected function normalizedCellsExist(): bool
    {
        foreach ($this->cells as $key => $value) {
            if (str_starts_with((string) $key, '_')) {
                continue;
            }
            if ($this->cellIsFilled($this->normalizeCellValue($value))) {
                return true;
            }
        }

        return false;
    }

    protected function maxFilledRow(): int
    {
        $max = 0;
        foreach ($this->cells as $key => $value) {
            if (! ctype_digit((string) $key)) {
                continue;
            }
            if ($this->cellIsFilled($this->normalizeCellValue($value))) {
                $max = max($max, (int) $key);
            }
        }

        return $max;
    }

    protected function pageCountForFilledRows(int $maxRow): int
    {
        if ($maxRow <= 0) {
            return 1;
        }
        $first = self::ROWS_PAGE1 * self::GRID_COLS;
        if ($maxRow <= $first) {
            return 1;
        }
        $cont = self::ROWS_CONT * self::GRID_COLS;

        return min(20, 1 + (int) ceil(($maxRow - $first) / $cont));
    }

    /**
     * @return array<int, string>
     */
    protected function digitSlots(): array
    {
        $slots = $this->settingsData()['digitSlots'] ?? [];

        return is_array($slots) ? $slots : [];
    }

    protected function encodeAmountValue(string $value): string
    {
        $normalized = trim(ReOrderSettingsForm::normalizeDigits($value));
        if ($normalized === '') {
            return '';
        }
        $slots = $this->digitSlots();
        if ($slots === []) {
            return $normalized;
        }
        $hasMapping = false;
        foreach ($slots as $token) {
            if (trim((string) $token) !== '') {
                $hasMapping = true;
                break;
            }
        }
        if (! $hasMapping) {
            return $normalized;
        }

        return app(SecretCodingEngine::class)->encodeWithSlots($normalized, $slots);
    }

    /**
     * @param  array<int, string>  $slots
     */
    protected function decodeAmountValue(string $raw, SecretCodingEngine $engine, array $slots): float
    {
        $paymentRaw = trim(ReOrderSettingsForm::normalizeDigits($raw));
        if ($paymentRaw === '') {
            return 0.0;
        }
        $decoded = $slots !== []
            ? $engine->decodeWithSlots($paymentRaw, $slots)
            : preg_replace('/[^\d.\-]/', '', $paymentRaw);
        $value = str_replace([',', ' '], '', trim((string) $decoded));
        if ($value === '' || ! is_numeric($value)) {
            return 0.0;
        }

        return (float) $value;
    }

    /**
     * @param  array<int, string>  $slots
     */
    protected function displayAmount(float $amount, SecretCodingEngine $engine, array $slots): string
    {
        $abs = abs($amount);
        if ($abs < 0.00001) {
            return '0';
        }
        $plain = abs($abs - round($abs)) < 0.00001
            ? (string) (int) round($abs)
            : rtrim(rtrim(number_format($abs, 2, '.', ''), '0'), '.');
        $encoded = $slots !== [] ? $engine->encodeWithSlots($plain, $slots) : $plain;

        return $amount < 0 ? '-'.$encoded : $encoded;
    }

    protected function emitSheetScreen(string $screen): void
    {
        $mode = $this->isViewMode() ? 'view' : 'edit';
        $this->dispatch('tools-pending-payments-screen', screen: $screen, formMode: $mode);
        $this->js(
            'window.dispatchEvent(new CustomEvent("tools-pending-payments-screen",{detail:{screen:'.json_encode($screen).',formMode:'.json_encode($mode).'}}))'
        );
    }

    protected function authorizeCreate(): void
    {
        $user = auth()->user();
        abort_unless(
            $user && ($user->isSuperAdmin() || ModulePageAccess::can($user, 'tools', 'pending-payments', 'create')),
            403
        );
    }

    public function getHeading(): string|Htmlable|null
    {
        return null;
    }

    public function getSubheading(): string|Htmlable|null
    {
        return null;
    }

    /**
     * @return array<string, string>
     */
    public function getBreadcrumbs(): array
    {
        return ToolsCluster::unshiftClusterBreadcrumbs([
            static::getUrl() => static::getNavigationLabel(),
        ]);
    }
}
