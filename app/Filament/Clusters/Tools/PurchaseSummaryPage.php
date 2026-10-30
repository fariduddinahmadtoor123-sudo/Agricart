<?php

namespace App\Filament\Clusters\Tools;

use App\Filament\Clusters\ToolsCluster;
use App\Filament\Pages\BaseModulePage;
use App\Livewire\Tools\ReOrderSettingsForm;
use App\Models\ToolsPurchaseSummary;
use App\Services\Access\ModulePageAccess;
use App\Services\PricingControls\SecretCodingEngine;
use App\Services\Tools\PurchaseSummarySettingsService;
use App\Services\Tools\ReOrderSettingsService;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use Livewire\Attributes\On;

class PurchaseSummaryPage extends BaseModulePage
{
    protected static ?string $cluster = ToolsCluster::class;

    protected static ?string $navigationLabel = 'Purchase Summary';

    protected static ?int $navigationSort = 3;

    protected static ?string $slug = 'purchase-summary';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected string $view = 'filament.pages.tools.purchase-summary';

    /** list | form */
    public string $screen = 'list';

    /** en | ur */
    public string $formLang = 'en';

    public string $summaryNo = '';

    public string $summaryDate = '';

    public string $preparedBy = '';

    public string $entryText = '';

    public string $forwardTo = '';

    /**
     * Row number => {payment, shopkeeper, status}.
     * status: '' | done | forward
     *
     * @var array<string, array{payment: string, shopkeeper: string, status: string}|string>
     */
    public array $cells = [];

    /** @var list<array{label: string, amount: string, date: string}> */
    public array $paymentSources = [];

    /** @var list<string> Pending bill slots (4 per row, grows by row). */
    public array $pendingBills = [];

    /** @var list<array{label: string, date: string}> Bills cleared on this summary (not forwarded). */
    public array $clearedPendingBills = [];

    public ?int $editingSummaryId = null;

    public int $pageCount = 1;

    public const ROWS_PAGE1 = 25;

    public const ROWS_CONT = 25;

    public const GRID_COLS = 2;

    public const PENDING_PER_ROW = 4;

    public const PENDING_MAX_SLOTS = 24;

    public string $draftFlash = '';

    public int $shopkeepersTick = 0;

    public string $search = '';

    public string $persistStatus = 'draft';

    /** edit | view */
    public string $formMode = 'edit';

    #[On('tools-purchase-summary-search')]
    public function updateSearch(string $query): void
    {
        $this->search = $query;
    }

    public function isViewMode(): bool
    {
        return $this->formMode === 'view' || $this->persistStatus === 'closed';
    }

    #[On('tools-purchase-summary-action')]
    public function handleToolbarAction(string $action): void
    {
        if ($action === 'new-summary') {
            $this->startNewSummary();

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
            'add-page' => $this->addSummaryPage(),
            'remove-page' => $this->removeSummaryPage(),
            'save-close' => $this->saveAndClose(),
            'close-form' => $this->closeSummaryForm(),
            'clear-all' => $this->clearAllSummary(),
            default => null,
        };
    }

    public function startNewSummary(): void
    {
        $this->authorizeCreate();

        if ($this->screen === 'form' && ! $this->isViewMode()) {
            $this->autoSaveDraft(skipRender: false);
        }

        $draft = ToolsPurchaseSummary::query()
            ->where('user_id', auth()->id())
            ->where('status', 'draft')
            ->latest('updated_at')
            ->first();

        if ($draft) {
            $this->applySummaryRecord($draft);
            $this->persistStatus = 'draft';
            $this->formMode = 'edit';
            $this->screen = 'form';
            $this->draftFlash = 'recovered';
            $this->emitSummaryScreen('form');
            Notification::make()
                ->title(__('agricart.pages.tools.ps_draft_recovered'))
                ->success()
                ->send();

            return;
        }

        if ($this->screen === 'form' && ! $this->isViewMode() && $this->draftHasContent()) {
            $this->autoSaveDraft(skipRender: false);
            $this->persistStatus = 'draft';
            $this->formMode = 'edit';
            $this->draftFlash = 'saved';
            $this->emitSummaryScreen('form');
            Notification::make()
                ->title(__('agricart.pages.tools.ps_draft_kept'))
                ->success()
                ->send();

            return;
        }

        $this->resetFreshSummary();
        $this->persistStatus = 'draft';
        $this->formMode = 'edit';
        $this->screen = 'form';
        $this->draftFlash = '';
        $this->emitSummaryScreen('form');
        $this->js('window.dispatchEvent(new CustomEvent("tools-purchase-summary-try-local-draft"))');
    }

    public function clearAllSummary(): void
    {
        if ($this->isViewMode()) {
            return;
        }

        $this->authorizeCreate();

        ToolsPurchaseSummary::query()
            ->where('user_id', auth()->id())
            ->where('status', 'draft')
            ->delete();

        $this->resetFreshSummary();
        $this->persistStatus = 'draft';
        $this->formMode = 'edit';
        $this->screen = 'form';
        $this->draftFlash = '';
        $this->shopkeepersTick++;
        $this->emitSummaryScreen('form');
        $this->js('window.dispatchEvent(new CustomEvent("tools-purchase-summary-clear-local-draft"))');

        Notification::make()
            ->title(__('agricart.pages.tools.ps_cleared'))
            ->success()
            ->send();
    }

    public function openSummary(int $id): void
    {
        $summary = ToolsPurchaseSummary::query()
            ->where('user_id', auth()->id())
            ->whereKey($id)
            ->firstOrFail();

        $mode = (string) $summary->status === 'closed' ? 'view' : 'edit';
        $this->loadSummaryIntoForm($id, $mode);
    }

    public function editSummary(int $id): void
    {
        $summary = ToolsPurchaseSummary::query()
            ->where('user_id', auth()->id())
            ->whereKey($id)
            ->firstOrFail();

        if ((string) $summary->status === 'closed') {
            Notification::make()
                ->title(__('agricart.pages.tools.ps_closed_locked'))
                ->warning()
                ->send();

            return;
        }

        $this->loadSummaryIntoForm($id, 'edit');
    }

    public function closeListedSummary(int $id): void
    {
        $this->authorizeCreate();

        $summary = ToolsPurchaseSummary::query()
            ->where('user_id', auth()->id())
            ->whereKey($id)
            ->where('status', 'saved')
            ->firstOrFail();

        $cells = is_array($summary->cells) ? $summary->cells : [];
        $unresolved = $this->unresolvedFilledLineCount($cells);

        if ($unresolved > 0) {
            Notification::make()
                ->title(__('agricart.pages.tools.ps_close_lines_required', ['count' => $unresolved]))
                ->warning()
                ->send();

            return;
        }

        $forwarded = $this->forwardedCellsFrom($cells);
        $pendingCarry = $this->normalizePendingBillsList($summary->pending_bills);
        $balance = $this->balanceFromStoredSummary($summary);
        $summary->update(['status' => 'closed']);

        $hasPending = collect($pendingCarry)->contains(fn ($v) => trim((string) $v) !== '');
        $hasBalance = $balance > 0.00001;

        if ($forwarded === [] && ! $hasPending && ! $hasBalance) {
            Notification::make()
                ->title(__('agricart.pages.tools.ps_closed_ok'))
                ->success()
                ->send();

            return;
        }

        $followUp = $this->createFollowUpSummaryFrom($summary, $forwarded, $pendingCarry, $balance);

        $carryCount = count($forwarded) + collect($pendingCarry)->filter(fn ($v) => trim((string) $v) !== '')->count();

        Notification::make()
            ->title(__('agricart.pages.tools.ps_closed_with_forward', [
                'count' => max(1, $carryCount),
                'summary' => $followUp->summary_no,
            ]))
            ->success()
            ->send();

        $this->loadSummaryIntoForm((int) $followUp->id, 'edit');
    }

    public function markLineStatus(string $rowKey, string $status): void
    {
        $this->authorizeCreate();

        if ($this->persistStatus === 'closed') {
            return;
        }

        if (! in_array($this->persistStatus, ['saved', 'draft'], true)) {
            return;
        }

        if ($status === 'clear') {
            $status = '';
        }

        if (! in_array($status, ['done', 'forward', ''], true)) {
            return;
        }

        if (! ctype_digit($rowKey)) {
            return;
        }

        // Ensure nested Livewire cell keys exist before read.
        if (! isset($this->cells[$rowKey]) || ! is_array($this->cells[$rowKey])) {
            $this->cells[$rowKey] = $this->normalizeCellValue($this->cells[$rowKey] ?? []);
        }

        $cell = $this->normalizeCellValue($this->cells[$rowKey] ?? []);

        if (! $this->cellIsFilled($cell)) {
            return;
        }

        $prevStatus = $cell['status'];
        $label = $this->pendingLabelForCell($cell);

        if ($prevStatus === 'forward' && $status !== 'forward') {
            $this->removeFromPendingBills($label);
        }

        // Green and red are exclusive.
        if ($status === 'done' && $prevStatus === 'forward') {
            $this->removeFromPendingBills($label);
        }

        $cell['status'] = $status;
        $this->cells[$rowKey] = $cell;

        if ($status === 'forward') {
            $this->addToPendingBills($label);
        }

        if ($this->persistStatus === 'saved') {
            $this->persistSavedSummaryCells();
        } else {
            // Must re-render so green/red dots update on screen.
            $this->autoSaveDraft(skipRender: false);
        }
    }

    public function canMarkLineStatus(): bool
    {
        return in_array($this->persistStatus, ['saved', 'draft'], true);
    }

    public function unresolvedLineCount(): int
    {
        return $this->unresolvedFilledLineCount($this->cells);
    }

    protected function loadSummaryIntoForm(int $id, string $mode): void
    {
        $this->authorizeCreate();

        $summary = ToolsPurchaseSummary::query()
            ->where('user_id', auth()->id())
            ->whereKey($id)
            ->firstOrFail();

        $this->applySummaryRecord($summary);
        $status = (string) $summary->status;
        $this->persistStatus = in_array($status, ['saved', 'draft', 'closed'], true) ? $status : 'draft';
        $this->formMode = $mode === 'view' || $status === 'closed' ? 'view' : 'edit';
        $this->screen = 'form';
        $this->draftFlash = '';
        $this->shopkeepersTick++;
        $this->emitSummaryScreen('form');
    }

    /**
     * @return \Illuminate\Support\Collection<int, ToolsPurchaseSummary>
     */
    public function listedSummaries()
    {
        $query = ToolsPurchaseSummary::query()
            ->where('user_id', auth()->id())
            ->whereIn('status', ['saved', 'closed'])
            ->latest('updated_at');

        $term = trim($this->search);
        if ($term !== '') {
            $like = '%'.$term.'%';
            $query->where(function ($q) use ($like): void {
                $q->where('summary_no', 'like', $like)
                    ->orWhere('summary_date', 'like', $like)
                    ->orWhere('prepared_by', 'like', $like)
                    ->orWhere('shop_name', 'like', $like);
            });
        }

        return $query->limit(150)->get();
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function restoreLocalDraft(array $payload): void
    {
        if ($this->screen !== 'form' || $this->editingSummaryId) {
            return;
        }

        if (! $this->draftLooksEmpty()) {
            return;
        }

        $this->applyLocalDraftPayload($payload);
        $this->draftFlash = 'recovered';
        Notification::make()
            ->title(__('agricart.pages.tools.ps_draft_recovered'))
            ->success()
            ->send();
    }

    public function closeSummaryForm(): void
    {
        if ($this->screen === 'form' && ! $this->isViewMode()) {
            $this->autoSaveDraft(skipRender: false);
        }

        $this->screen = 'list';
        $this->formMode = 'edit';
        $this->entryText = '';
        $this->forwardTo = '';
        $this->draftFlash = '';
        $this->emitSummaryScreen('list');
    }

    public function autoSaveDraft(bool $skipRender = true): void
    {
        if ($this->screen !== 'form' || $this->isViewMode()) {
            return;
        }

        if (! $this->draftHasContent()) {
            return;
        }

        $this->authorizeCreate();

        $summaryNo = trim($this->summaryNo);
        if ($summaryNo === '') {
            $summaryNo = app(PurchaseSummarySettingsService::class)->peekSummaryNumber();
            $this->summaryNo = $summaryNo;
        }

        $status = $this->persistStatus === 'saved' ? 'saved' : 'draft';
        $payload = $this->summaryPayload($status);
        $payload['summary_no'] = $summaryNo;

        $summary = $this->upsertSummarySafely($payload, $summaryNo, preferDraft: true);

        $this->editingSummaryId = (int) $summary->id;
        $this->summaryNo = (string) $summary->summary_no;
        $this->draftFlash = 'saved';

        if ($skipRender) {
            $this->skipRender();
        }
    }

    public function addPaymentSource(): void
    {
        if ($this->isViewMode()) {
            return;
        }
        $this->authorizeCreate();
        $this->paymentSources[] = [
            'label' => '',
            'amount' => '',
            'date' => $this->defaultPaymentSourceDate(),
        ];
    }

    public function removePaymentSource(int $index): void
    {
        if ($this->isViewMode()) {
            return;
        }
        $this->authorizeCreate();
        if (! isset($this->paymentSources[$index])) {
            return;
        }
        unset($this->paymentSources[$index]);
        $this->paymentSources = array_values($this->paymentSources);
    }

    protected function resetFreshSummary(): void
    {
        $user = auth()->user();
        $this->editingSummaryId = null;
        $this->formLang = 'en';
        $this->summaryNo = app(PurchaseSummarySettingsService::class)->peekSummaryNumber();
        $this->summaryDate = now(config('app.display_timezone', config('app.timezone')))->format('d-m-Y');
        $this->preparedBy = (string) ($user?->name ?? '');
        $this->entryText = '';
        $this->forwardTo = '';
        $this->cells = [];
        $this->paymentSources = [];
        $this->pendingBills = $this->emptyPendingSlots();
        $this->clearedPendingBills = [];
        $this->pageCount = 1;
        $this->draftFlash = '';
    }

    protected function applySummaryRecord(ToolsPurchaseSummary $summary): void
    {
        $this->editingSummaryId = (int) $summary->id;
        $this->formLang = in_array($summary->lang, ['en', 'ur'], true) ? (string) $summary->lang : 'en';
        $this->summaryNo = (string) $summary->summary_no;
        $this->summaryDate = (string) $summary->summary_date;
        $this->preparedBy = (string) ($summary->prepared_by ?? '');
        $this->entryText = '';
        $this->forwardTo = '';
        $this->cells = is_array($summary->cells) ? $summary->cells : [];
        $this->paymentSources = $this->normalizePaymentSources($summary->payment_sources);
        $this->pendingBills = $this->normalizePendingBillsList($summary->pending_bills);
        $this->clearedPendingBills = [];
        $this->normalizeCellsInPlace();
        $this->pageCount = max($this->pageCount, $this->pageCountForFilledRows($this->maxFilledRow()));
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function applyLocalDraftPayload(array $payload): void
    {
        $this->formLang = in_array($payload['formLang'] ?? '', ['en', 'ur'], true)
            ? (string) $payload['formLang']
            : $this->formLang;
        if (isset($payload['summaryNo'])) {
            $this->summaryNo = (string) $payload['summaryNo'];
        }
        if (isset($payload['summaryDate'])) {
            $this->summaryDate = (string) $payload['summaryDate'];
        }
        if (isset($payload['preparedBy'])) {
            $this->preparedBy = (string) $payload['preparedBy'];
        }
        if (isset($payload['entryText'])) {
            $this->entryText = (string) $payload['entryText'];
        }
        if (isset($payload['forwardTo'])) {
            $this->forwardTo = (string) $payload['forwardTo'];
        }
        if (is_array($payload['cells'] ?? null)) {
            $this->cells = $payload['cells'];
        }
        if (is_array($payload['paymentSources'] ?? null)) {
            $this->paymentSources = $this->normalizePaymentSources($payload['paymentSources']);
        }
        if (is_array($payload['pendingBills'] ?? null)) {
            $this->pendingBills = $this->normalizePendingBillsList($payload['pendingBills']);
        }
        if (is_array($payload['clearedPendingBills'] ?? null)) {
            $this->clearedPendingBills = $this->normalizeClearedPendingList($payload['clearedPendingBills']);
        }
        if (isset($payload['pageCount'])) {
            $this->pageCount = max(1, min(20, (int) $payload['pageCount']));
        }
        $this->normalizeCellsInPlace();
    }

    protected function draftHasContent(): bool
    {
        return ! $this->draftLooksEmpty();
    }

    protected function draftLooksEmpty(): bool
    {
        return trim($this->entryText) === ''
            && $this->normalizedCellsForSave() === []
            && $this->normalizedPaymentSourcesForSave() === []
            && $this->normalizedPendingBillsForSave() === [];
    }

    protected function maxFilledRow(): int
    {
        $max = 0;
        foreach ($this->cells as $key => $value) {
            if (! ctype_digit((string) $key)) {
                continue;
            }
            $row = $this->normalizeCellValue($value);
            if ($row['payment'] === '' && $row['shopkeeper'] === '') {
                continue;
            }
            $max = max($max, (int) $key);
        }

        return $max;
    }

    /**
     * @return array<string, mixed>
     */
    protected function summaryPayload(string $status): array
    {
        return [
            'user_id' => (int) auth()->id(),
            'summary_date' => trim($this->summaryDate),
            'lang' => $this->formLang,
            'shop_name' => $this->shopName(),
            'shop_address' => $this->shopAddress(),
            'shop_phones' => $this->shopPhones(),
            'prepared_by' => trim($this->preparedBy),
            'cells' => $this->cellsPayloadForSave(),
            'payment_sources' => $this->normalizedPaymentSourcesForSave(),
            'pending_bills' => $this->normalizedPendingBillsForSave(),
            'status' => $status,
        ];
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
            'window.dispatchEvent(new CustomEvent("tools-purchase-summary-lang-changed",{detail:{lang:'.json_encode($lang).'}}))'
        );
    }

    protected function emitSummaryScreen(string $screen): void
    {
        $mode = $screen === 'form' ? $this->formMode : 'edit';
        $this->dispatch('tools-purchase-summary-screen', screen: $screen, formMode: $mode);
        $this->js(
            'window.dispatchEvent(new CustomEvent("tools-purchase-summary-screen",{detail:{screen:'.json_encode($screen).',formMode:'.json_encode($mode).'}}))'
        );
    }

    public function clearEntry(): void
    {
        $this->entryText = '';
    }

    public function forwardEntryFromUi(string $text = '', string $box = ''): void
    {
        if (trim($text) !== '') {
            $this->entryText = $text;
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

        $text = trim($this->entryText);
        $to = ReOrderSettingsForm::normalizeDigits(trim($this->forwardTo));
        $to = preg_replace('/\D+/', '', (string) $to) ?? '';

        if ($text === '') {
            Notification::make()
                ->title(__('agricart.pages.tools.ps_forward_need_text'))
                ->warning()
                ->send();

            return;
        }

        if ($to === '' || ! ctype_digit($to) || (int) $to < 1) {
            Notification::make()
                ->title(__('agricart.pages.tools.ps_forward_need_box'))
                ->warning()
                ->send();

            return;
        }

        $rowKey = (string) ((int) $to);
        $needed = $this->pageCountForFilledRows((int) $rowKey);
        if ($needed > $this->pageCount) {
            $this->pageCount = min(20, $needed);
        }

        $row = $this->normalizeCellValue($this->cells[$rowKey] ?? []);
        $row['shopkeeper'] = $text;
        $row['status'] = '';
        $this->cells[$rowKey] = $row;
        $this->entryText = '';
        $this->forwardTo = '';
        $this->rememberShopkeeper($text);
        $this->shopkeepersTick++;
        $this->autoSaveDraft(skipRender: false);

        $this->js(
            'window.dispatchEvent(new CustomEvent("tools-purchase-summary-entry-cleared"));'
            .'requestAnimationFrame(()=>{const el=document.querySelector(".agricart-ps-entry__input");if(el){el.focus();}});'
        );
    }

    public function updated(string $name, mixed $value): void
    {
        if (preg_match('/^cells\.(\d+)\.payment$/', $name, $m) === 1) {
            $key = $m[1];
            $cell = $this->normalizeCellValue($this->cells[$key] ?? []);
            $cell['payment'] = $this->encodePaymentValue((string) $value);
            $cell['status'] = '';
            $this->cells[$key] = $cell;

            return;
        }

        if (preg_match('/^paymentSources\.(\d+)\.amount$/', $name, $m) === 1) {
            $index = (int) $m[1];
            if (! isset($this->paymentSources[$index])) {
                return;
            }
            $row = $this->paymentSources[$index];
            $row['amount'] = $this->encodePaymentValue((string) $value);
            $this->paymentSources[$index] = $row;

            return;
        }

        if (preg_match('/^cells\.(\d+)\.shopkeeper$/', $name, $m) === 1) {
            $key = $m[1];
            $cell = $this->normalizeCellValue($this->cells[$key] ?? []);
            $cell['status'] = '';
            $this->cells[$key] = $cell;
            $this->rememberShopkeeper((string) ($cell['shopkeeper'] ?? $value));
        }
    }

    public function rememberShopkeeper(string $text): void
    {
        if ($this->isViewMode()) {
            return;
        }

        $text = trim($text);
        if (mb_strlen($text) < 2) {
            return;
        }

        $names = app(PurchaseSummarySettingsService::class)->rememberShopkeeper($text);
        $this->js(
            'window.__agricartPsShopkeepers = '.json_encode(array_values($names), JSON_UNESCAPED_UNICODE).';'
            .'window.dispatchEvent(new CustomEvent("tools-purchase-summary-shopkeepers-updated"));'
        );
    }

    /**
     * @return list<string>
     */
    public function shopkeeperOptions(): array
    {
        return app(PurchaseSummarySettingsService::class)->shopkeepersForUser();
    }

    protected function encodePaymentValue(string $value): string
    {
        $normalized = trim(ReOrderSettingsForm::normalizeDigits($value));

        if ($normalized === '') {
            return '';
        }

        $slots = $this->settingsData()['digitSlots'] ?? [];

        if (! is_array($slots) || $slots === []) {
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
     * Payment available / spent on lines / remaining balance to forward.
     *
     * @return array{
     *     sources: float,
     *     spent: float,
     *     balance: float,
     *     priced: int,
     *     sources_plain: string,
     *     sources_encoded: string,
     *     spent_plain: string,
     *     spent_encoded: string,
     *     balance_plain: string,
     *     balance_encoded: string,
     *     plain: string,
     *     encoded: string
     * }
     */
    public function summaryAmountSummary(): array
    {
        $engine = app(SecretCodingEngine::class);
        $slots = $this->settingsData()['digitSlots'] ?? [];
        $slots = is_array($slots) ? $slots : [];

        $sources = 0.0;
        foreach ($this->paymentSources as $source) {
            $sources += $this->decodeAmountValue((string) ($source['amount'] ?? ''), $engine, $slots);
        }

        $spent = 0.0;
        $priced = 0;
        foreach ($this->cells as $key => $value) {
            if (! ctype_digit((string) $key) || str_starts_with((string) $key, '_')) {
                continue;
            }
            $row = $this->normalizeCellValue($value);
            if (! $this->cellIsFilled($row)) {
                continue;
            }
            $amount = $this->decodeAmountValue($row['payment'], $engine, $slots);
            if ($amount <= 0) {
                continue;
            }
            $spent += $amount;
            $priced++;
        }

        $balance = $sources - $spent;
        $sourcesPlain = $this->formatAmountNumber($sources);
        $spentPlain = $this->formatAmountNumber($spent);
        $balancePlain = $this->formatAmountNumber($balance);
        $encode = function (string $plain) use ($engine, $slots): string {
            if ($plain === '' || $plain === '0') {
                return '';
            }

            return $slots !== [] ? $engine->encodeWithSlots(ltrim($plain, '-'), $slots) : $plain;
        };

        $balanceDisplayPlain = $balance < 0
            ? '-'.$this->formatAmountNumber(abs($balance))
            : $balancePlain;
        $balanceEncoded = $balance < 0
            ? '-'.$encode($this->formatAmountNumber(abs($balance)))
            : $encode($balancePlain);

        return [
            'sources' => $sources,
            'spent' => $spent,
            'balance' => $balance,
            'priced' => $priced,
            'sources_plain' => $sourcesPlain,
            'sources_encoded' => $encode($sourcesPlain),
            'spent_plain' => $spentPlain,
            'spent_encoded' => $encode($spentPlain),
            'balance_plain' => $balanceDisplayPlain,
            'balance_encoded' => $balanceEncoded,
            'plain' => $spentPlain,
            'encoded' => $encode($spentPlain),
            'total' => $spent,
        ];
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

        return $this->parseLooseNumber((string) $decoded);
    }

    protected function defaultPaymentSourceDate(): string
    {
        $date = trim($this->summaryDate);
        if ($date !== '') {
            return $date;
        }

        return now(config('app.display_timezone', config('app.timezone')))->format('d-m-Y');
    }

    /**
     * @param  array{payment: string, shopkeeper: string, status: string}  $cell
     */
    protected function pendingLabelForCell(array $cell): string
    {
        $shopkeeper = trim($cell['shopkeeper']);
        $payment = trim($cell['payment']);
        if ($shopkeeper !== '' && $payment !== '') {
            return $shopkeeper.' '.$payment;
        }

        return $shopkeeper !== '' ? $shopkeeper : $payment;
    }

    protected function emptyPendingSlots(int $rows = 1): array
    {
        $rows = max(1, $rows);

        return array_fill(0, min(self::PENDING_MAX_SLOTS, $rows * self::PENDING_PER_ROW), '');
    }

    protected function addToPendingBills(string $label): void
    {
        $label = trim($label);
        if ($label === '') {
            return;
        }
        $this->pendingBills = $this->normalizePendingBillsList($this->pendingBills);
        foreach ($this->pendingBills as $slot) {
            if (trim((string) $slot) === $label) {
                return;
            }
        }
        for ($i = 0; $i < count($this->pendingBills); $i++) {
            if (trim((string) ($this->pendingBills[$i] ?? '')) === '') {
                $this->pendingBills[$i] = $label;
                $this->pendingBills = $this->normalizePendingBillsList($this->pendingBills);

                return;
            }
        }
        if (count($this->pendingBills) >= self::PENDING_MAX_SLOTS) {
            return;
        }
        $this->pendingBills = array_values(array_merge(
            $this->pendingBills,
            $this->emptyPendingSlots(1)
        ));
        $this->pendingBills = array_slice($this->pendingBills, 0, self::PENDING_MAX_SLOTS);
        for ($i = 0; $i < count($this->pendingBills); $i++) {
            if (trim((string) ($this->pendingBills[$i] ?? '')) === '') {
                $this->pendingBills[$i] = $label;
                break;
            }
        }
        $this->pendingBills = $this->normalizePendingBillsList($this->pendingBills);
    }

    protected function removeFromPendingBills(string $label): void
    {
        $label = trim($label);
        if ($label === '') {
            return;
        }
        $kept = [];
        foreach ($this->normalizePendingBillsList($this->pendingBills) as $slot) {
            $slot = trim((string) $slot);
            if ($slot === '' || $slot === $label) {
                continue;
            }
            $kept[] = $slot;
        }
        $this->pendingBills = $this->normalizePendingBillsList($kept);
    }

    protected function balanceFromStoredSummary(ToolsPurchaseSummary $summary): float
    {
        return max(0.0, (float) ($this->listMoneySummary($summary)['balance'] ?? 0));
    }

    /**
     * Money snapshot for a saved/closed summary (list page).
     *
     * @return array{
     *     sources: float,
     *     spent: float,
     *     balance: float,
     *     pending_count: int,
     *     sources_display: string,
     *     spent_display: string,
     *     balance_display: string
     * }
     */
    public function listMoneySummary(ToolsPurchaseSummary $summary): array
    {
        $engine = app(SecretCodingEngine::class);
        $slots = $this->settingsData()['digitSlots'] ?? [];
        $slots = is_array($slots) ? $slots : [];

        $sources = 0.0;
        foreach ($this->normalizePaymentSources($summary->payment_sources) as $source) {
            $sources += $this->decodeAmountValue((string) ($source['amount'] ?? ''), $engine, $slots);
        }

        $spent = 0.0;
        $cells = is_array($summary->cells) ? $summary->cells : [];
        foreach ($cells as $key => $value) {
            if (! ctype_digit((string) $key) || str_starts_with((string) $key, '_')) {
                continue;
            }
            $row = $this->normalizeCellValue($value);
            if (! $this->cellIsFilled($row)) {
                continue;
            }
            $spent += $this->decodeAmountValue($row['payment'], $engine, $slots);
        }

        $balance = $sources - $spent;
        $pendingCount = 0;
        foreach ($this->normalizePendingBillsList($summary->pending_bills) as $slot) {
            if (trim((string) $slot) !== '') {
                $pendingCount++;
            }
        }

        $encode = function (float $amount) use ($engine, $slots): string {
            $plain = $this->formatAmountNumber(abs($amount));
            if ($plain === '' || $plain === '0') {
                return '0';
            }
            $encoded = $slots !== [] ? $engine->encodeWithSlots($plain, $slots) : $plain;
            if ($amount < 0) {
                return '-'.$encoded;
            }

            return $encoded;
        };

        return [
            'sources' => $sources,
            'spent' => $spent,
            'balance' => $balance,
            'pending_count' => $pendingCount,
            'sources_display' => $encode($sources),
            'spent_display' => $encode($spent),
            'balance_display' => $encode($balance),
        ];
    }

    protected function parseLooseNumber(string $value): float
    {
        $value = str_replace([',', ' '], '', trim($value));
        if ($value === '' || ! is_numeric($value)) {
            return 0.0;
        }

        return (float) $value;
    }

    protected function formatAmountNumber(float $total): string
    {
        if ($total <= 0) {
            return '0';
        }

        if (abs($total - round($total)) < 0.00001) {
            return (string) (int) round($total);
        }

        return rtrim(rtrim(number_format($total, 2, '.', ''), '0'), '.');
    }

    public function saveAndClose(): void
    {
        if ($this->isViewMode()) {
            return;
        }

        $this->authorizeCreate();

        $service = app(PurchaseSummarySettingsService::class);
        $payload = $this->summaryPayload('saved');
        $peeked = $service->peekSummaryNumber();
        $summaryNo = trim($this->summaryNo);

        if ($summaryNo === '' || $summaryNo === $peeked) {
            $summaryNo = $service->nextSummaryNumber();
            $this->summaryNo = $summaryNo;
        }
        $payload['summary_no'] = $summaryNo;

        $this->upsertSummarySafely($payload, $payload['summary_no'], preferDraft: false);

        $this->js('window.dispatchEvent(new CustomEvent("tools-purchase-summary-clear-local-draft"))');
        $this->editingSummaryId = null;
        $this->persistStatus = 'draft';
        $this->screen = 'list';
        $this->entryText = '';
        $this->forwardTo = '';
        $this->draftFlash = '';
        $this->cells = [];
        $this->paymentSources = [];
        $this->pendingBills = $this->emptyPendingSlots();
        $this->clearedPendingBills = [];
        $this->emitSummaryScreen('list');
    }

    public function mount(): void
    {
        $this->pendingBills = $this->normalizePendingBillsList($this->pendingBills);
        $this->clearedPendingBills = $this->normalizeClearedPendingList($this->clearedPendingBills);
        $this->normalizeCellsInPlace();
        $this->emitSummaryScreen($this->screen);
    }

    public function hydrate(): void
    {
        $this->pendingBills = $this->normalizePendingBillsList($this->pendingBills);
        $this->clearedPendingBills = $this->normalizeClearedPendingList($this->clearedPendingBills);
        $this->normalizeCellsInPlace();
    }

    public function clearPendingBill(int $index): void
    {
        if ($this->isViewMode()) {
            return;
        }
        $this->authorizeCreate();

        $this->pendingBills = $this->normalizePendingBillsList($this->pendingBills);
        $label = trim((string) ($this->pendingBills[$index] ?? ''));
        if ($label === '') {
            return;
        }

        $this->pendingBills[$index] = '';
        $this->pendingBills = $this->normalizePendingBillsList($this->pendingBills);

        $date = trim($this->summaryDate);
        if ($date === '') {
            $date = now(config('app.display_timezone', config('app.timezone')))->format('d-m-Y');
        }

        $this->clearedPendingBills = $this->normalizeClearedPendingList($this->clearedPendingBills);
        $this->clearedPendingBills[] = [
            'label' => $label,
            'date' => $date,
        ];

        if ($this->persistStatus === 'saved') {
            $this->persistSavedSummaryCells();
        } else {
            $this->autoSaveDraft(skipRender: false);
        }
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
            if ($key === '_clearedPending') {
                $this->clearedPendingBills = $this->normalizeClearedPendingList($value);

                continue;
            }
            if (str_starts_with($key, '_')) {
                continue;
            }
            $out[$key] = $this->normalizeCellValue($value);
        }
        $this->cells = $out;
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
     * @return array<string, mixed>
     */
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

    /**
     * @return array{payment: string, shopkeeper: string, status: string}
     */
    public function cellRow(string|int $rowNo): array
    {
        return $this->normalizeCellValue($this->cells[(string) $rowNo] ?? []);
    }

    /**
     * @return array{payment: string, shopkeeper: string, status: string}
     */
    protected function normalizeCellValue(mixed $value): array
    {
        if (is_string($value)) {
            return [
                'payment' => '',
                'shopkeeper' => $value,
                'status' => '',
            ];
        }

        if (! is_array($value)) {
            return [
                'payment' => '',
                'shopkeeper' => '',
                'status' => '',
            ];
        }

        $status = (string) ($value['status'] ?? '');
        if (! in_array($status, ['done', 'forward'], true)) {
            $status = '';
        }

        return [
            'payment' => (string) ($value['payment'] ?? ''),
            'shopkeeper' => (string) ($value['shopkeeper'] ?? ''),
            'status' => $status,
        ];
    }

    /**
     * @param  array{payment: string, shopkeeper: string, status: string}  $cell
     */
    protected function cellIsFilled(array $cell): bool
    {
        return $cell['payment'] !== '' || $cell['shopkeeper'] !== '';
    }

    /**
     * @param  array<string, mixed>  $cells
     */
    public function unresolvedFilledLineCount(array $cells): int
    {
        $count = 0;

        foreach ($cells as $key => $value) {
            if (! ctype_digit((string) $key) || str_starts_with((string) $key, '_')) {
                continue;
            }
            $row = $this->normalizeCellValue($value);
            if (! $this->cellIsFilled($row)) {
                continue;
            }
            if ($row['status'] !== 'done' && $row['status'] !== 'forward') {
                $count++;
            }
        }

        return $count;
    }

    /**
     * @param  array<string, mixed>  $cells
     * @return list<array{payment: string, shopkeeper: string, status: string}>
     */
    protected function forwardedCellsFrom(array $cells): array
    {
        $out = [];
        $keys = [];

        foreach ($cells as $key => $value) {
            if (! ctype_digit((string) $key)) {
                continue;
            }
            $keys[] = (int) $key;
        }
        sort($keys);

        foreach ($keys as $key) {
            $row = $this->normalizeCellValue($cells[(string) $key] ?? []);
            if (! $this->cellIsFilled($row) || $row['status'] !== 'forward') {
                continue;
            }
            $row['status'] = '';
            $out[] = $row;
        }

        return $out;
    }

    /**
     * @param  list<array{payment: string, shopkeeper: string, status: string}>  $forwarded
     * @param  list<string>  $pendingBills
     */
    protected function createFollowUpSummaryFrom(
        ToolsPurchaseSummary $source,
        array $forwarded,
        array $pendingBills = [],
        float $balance = 0.0,
    ): ToolsPurchaseSummary {
        $service = app(PurchaseSummarySettingsService::class);
        $summaryNo = $service->nextSummaryNumber();

        // Red-marked lines go only into pending bills on the next list (not the grid).
        $pendingFilled = [];
        foreach ($this->normalizePendingBillsList($pendingBills !== [] ? $pendingBills : $source->pending_bills) as $slot) {
            $slot = trim((string) $slot);
            if ($slot !== '') {
                $pendingFilled[] = $slot;
            }
        }
        foreach (array_values($forwarded) as $row) {
            $label = $this->pendingLabelForCell($row);
            if ($label === '' || in_array($label, $pendingFilled, true)) {
                continue;
            }
            $pendingFilled[] = $label;
        }
        $pendingFilled = array_slice($pendingFilled, 0, self::PENDING_MAX_SLOTS);

        $today = now(config('app.display_timezone', config('app.timezone')))->format('d-m-Y');
        $paymentSources = [];
        if ($balance > 0.00001) {
            $engine = app(SecretCodingEngine::class);
            $slots = $this->settingsData()['digitSlots'] ?? [];
            $slots = is_array($slots) ? $slots : [];
            $plain = $this->formatAmountNumber($balance);
            $amount = $plain === '' || $plain === '0'
                ? ''
                : ($slots !== [] ? $engine->encodeWithSlots($plain, $slots) : $plain);
            $labelLocale = in_array((string) ($source->lang ?? ''), ['en', 'ur'], true)
                ? (string) $source->lang
                : 'en';
            $paymentSources[] = [
                'label' => __('agricart.pages.tools.ps_balance_forward_label', [], $labelLocale),
                'amount' => $amount,
                'date' => $today,
            ];
        }

        return ToolsPurchaseSummary::query()->create([
            'user_id' => (int) $source->user_id,
            'summary_no' => $summaryNo,
            'summary_date' => $today,
            'lang' => (string) ($source->lang ?: 'en'),
            'shop_name' => (string) ($source->shop_name ?? ''),
            'shop_address' => (string) ($source->shop_address ?? ''),
            'shop_phones' => is_array($source->shop_phones) ? $source->shop_phones : [],
            'prepared_by' => (string) ($source->prepared_by ?? ''),
            'cells' => ['_pageCount' => 1],
            'payment_sources' => $paymentSources,
            'pending_bills' => $pendingFilled !== [] ? $pendingFilled : [],
            'status' => 'draft',
        ]);
    }

    protected function persistSavedSummaryCells(): void
    {
        if (! $this->editingSummaryId || $this->persistStatus !== 'saved') {
            return;
        }

        $summary = ToolsPurchaseSummary::query()
            ->where('user_id', auth()->id())
            ->whereKey($this->editingSummaryId)
            ->where('status', 'saved')
            ->first();

        if (! $summary) {
            return;
        }

        $summary->update([
            'cells' => $this->cellsPayloadForSave(),
            'payment_sources' => $this->normalizedPaymentSourcesForSave(),
            'pending_bills' => $this->normalizedPendingBillsForSave(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function cellsPayloadForSave(): array
    {
        $out = $this->normalizedCellsForSave();
        $out['_pageCount'] = max(1, min(20, $this->pageCount));
        $cleared = $this->normalizedClearedPendingForSave();
        if ($cleared !== []) {
            $out['_clearedPending'] = $cleared;
        }

        return $out;
    }

    /**
     * @return list<array{label: string, date: string}>
     */
    protected function normalizedClearedPendingForSave(): array
    {
        return $this->normalizeClearedPendingList($this->clearedPendingBills);
    }

    /**
     * @return list<array{label: string, date: string}>
     */
    protected function normalizeClearedPendingList(mixed $raw): array
    {
        if (! is_array($raw)) {
            return [];
        }
        $out = [];
        foreach ($raw as $row) {
            if (! is_array($row)) {
                continue;
            }
            $label = trim((string) ($row['label'] ?? ''));
            $date = trim((string) ($row['date'] ?? ''));
            if ($label === '') {
                continue;
            }
            $out[] = [
                'label' => $label,
                'date' => $date !== '' ? $date : trim($this->summaryDate),
            ];
        }

        return $out;
    }

    /**
     * @return array<string, array{payment: string, shopkeeper: string, status: string}>
     */
    protected function normalizedCellsForSave(): array
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

        return $out;
    }

    /**
     * @return list<array{label: string, amount: string, date: string}>
     */
    protected function normalizedPaymentSourcesForSave(): array
    {
        $out = [];
        foreach ($this->paymentSources as $row) {
            if (! is_array($row)) {
                continue;
            }
            $label = trim((string) ($row['label'] ?? ''));
            $amount = trim((string) ($row['amount'] ?? ''));
            $date = trim((string) ($row['date'] ?? ''));
            if ($label === '' && $amount === '') {
                continue;
            }
            if ($date === '') {
                $date = $this->defaultPaymentSourceDate();
            }
            $out[] = ['label' => $label, 'amount' => $amount, 'date' => $date];
        }

        return $out;
    }

    /**
     * @return list<string>
     */
    protected function normalizedPendingBillsForSave(): array
    {
        $out = [];
        foreach ($this->normalizePendingBillsList($this->pendingBills) as $slot) {
            $slot = trim((string) $slot);
            if ($slot !== '') {
                $out[] = $slot;
            }
        }

        return $out;
    }

    /**
     * Keep filled bills compacted, then pad to full rows of 4 (extra empty row when full).
     *
     * @return list<string>
     */
    protected function normalizePendingBillsList(mixed $raw): array
    {
        $filled = [];
        if (is_array($raw)) {
            foreach ($raw as $slot) {
                $slot = trim((string) $slot);
                if ($slot !== '') {
                    $filled[] = $slot;
                }
            }
        }
        $filled = array_slice($filled, 0, self::PENDING_MAX_SLOTS);
        $count = count($filled);
        $rows = max(1, (int) ceil(max($count, 1) / self::PENDING_PER_ROW));
        if ($count > 0 && $count % self::PENDING_PER_ROW === 0 && ($rows * self::PENDING_PER_ROW) < self::PENDING_MAX_SLOTS) {
            $rows++;
        }
        $slots = min(self::PENDING_MAX_SLOTS, $rows * self::PENDING_PER_ROW);
        $out = array_fill(0, $slots, '');
        foreach ($filled as $i => $value) {
            $out[$i] = $value;
        }

        return $out;
    }

    /**
     * @return list<array{label: string, amount: string, date: string}>
     */
    protected function normalizePaymentSources(mixed $raw): array
    {
        if (! is_array($raw)) {
            return [];
        }
        $out = [];
        $fallbackDate = $this->defaultPaymentSourceDate();
        foreach ($raw as $row) {
            if (! is_array($row)) {
                continue;
            }
            $date = trim((string) ($row['date'] ?? ''));
            $out[] = [
                'label' => trim((string) ($row['label'] ?? '')),
                'amount' => trim((string) ($row['amount'] ?? '')),
                'date' => $date !== '' ? $date : $fallbackDate,
            ];
        }

        return $out;
    }

    public function addSummaryPage(): void
    {
        if ($this->isViewMode()) {
            return;
        }
        $this->authorizeCreate();
        $this->pageCount = min(20, max(1, $this->pageCount + 1));
    }

    public function removeSummaryPage(): void
    {
        if ($this->isViewMode()) {
            return;
        }
        $this->authorizeCreate();
        if ($this->pageCount <= 1) {
            return;
        }
        $this->pageCount--;
    }

    protected function authorizeCreate(): void
    {
        $user = auth()->user();
        abort_unless(
            $user && ($user->isSuperAdmin() || ModulePageAccess::can($user, 'tools', 'purchase-summary', 'create')),
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

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function upsertSummarySafely(array $payload, string $summaryNo, bool $preferDraft): ToolsPurchaseSummary
    {
        $userId = (int) auth()->id();
        $service = app(PurchaseSummarySettingsService::class);

        if ($this->editingSummaryId) {
            $row = ToolsPurchaseSummary::query()
                ->where('user_id', $userId)
                ->whereKey($this->editingSummaryId)
                ->first();

            if ($row !== null) {
                if ((string) $row->status === 'closed' && $preferDraft) {
                    $summaryNo = $service->nextSummaryNumber();
                    $payload['summary_no'] = $summaryNo;
                    $payload['status'] = 'draft';
                    $created = ToolsPurchaseSummary::query()->create(array_merge($payload, ['user_id' => $userId]));
                    $this->editingSummaryId = (int) $created->id;

                    return $created;
                }

                $payload['summary_no'] = (string) $row->summary_no;
                if ($preferDraft && (string) $row->status === 'saved') {
                    unset($payload['status']);
                }
                $row->update($payload);

                return $row->fresh() ?? $row;
            }
        }

        $existing = ToolsPurchaseSummary::query()
            ->where('user_id', $userId)
            ->where('summary_no', $summaryNo)
            ->first();

        if ($existing !== null) {
            if (in_array((string) $existing->status, ['saved', 'closed'], true) && $preferDraft) {
                $summaryNo = $service->nextSummaryNumber();
                $payload['summary_no'] = $summaryNo;
                $payload['status'] = 'draft';

                return ToolsPurchaseSummary::query()->create(array_merge($payload, ['user_id' => $userId]));
            }

            if ((string) $existing->status === 'closed' && ! $preferDraft) {
                $summaryNo = $service->nextSummaryNumber();
                $payload['summary_no'] = $summaryNo;

                return ToolsPurchaseSummary::query()->create(array_merge($payload, ['user_id' => $userId]));
            }

            $existing->update($payload);

            return $existing->fresh() ?? $existing;
        }

        return ToolsPurchaseSummary::query()->create(array_merge($payload, ['user_id' => $userId]));
    }
}
