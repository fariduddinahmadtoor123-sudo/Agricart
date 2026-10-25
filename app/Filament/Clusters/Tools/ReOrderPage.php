<?php

namespace App\Filament\Clusters\Tools;

use App\Filament\Clusters\ToolsCluster;
use App\Filament\Pages\BaseModulePage;
use App\Models\ToolsReorderOrder;
use App\Services\Access\ModulePageAccess;
use App\Livewire\Tools\ReOrderSettingsForm;
use App\Services\PricingControls\SecretCodingEngine;
use App\Services\Tools\ReOrderSettingsService;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use Livewire\Attributes\On;

class ReOrderPage extends BaseModulePage
{
    protected static ?string $cluster = ToolsCluster::class;

    protected static ?string $navigationLabel = 'Re Order';

    protected static ?int $navigationSort = 2;

    protected static ?string $slug = 're-order';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowPath;

    protected string $view = 'filament.pages.tools.re-order';

    /** list | form */
    public string $screen = 'list';

    /** en | ur */
    public string $formLang = 'en';

    public string $orderNo = '';

    public string $orderDate = '';

    public string $purchaserKey = '';

    public string $preparedBy = '';

    public string $entryText = '';

    public string $forwardTo = '';

    /**
     * Row number => {detail, unit, qty, rate, status}.
     * status: '' | done | forward — fulfillment mark after shopping (screen only).
     * Legacy string values are normalized to detail-only rows.
     *
     * @var array<string, array{detail: string, unit: string, qty: string, rate: string, status: string}|string>
     */
    public array $cells = [];

    /**
     * Footer notes per page: page number => list of up to 4 cell texts.
     *
     * @var array<int|string, array<int|string, string>>
     */
    public array $pendingNotes = [];

    /**
     * @var array<int|string, array<int|string, string>>
     */
    public array $returnNotes = [];

    public ?int $editingOrderId = null;

    /** Total A4 pages in this order (page 1 = full header, others = grid-only). */
    public int $pageCount = 1;

    /** Rows per column on page 1 (header + footer). */
    public const ROWS_PAGE1 = 24;

    /** Rows per column on continuation pages (grid fills the sheet under strip). */
    public const ROWS_CONT = 31;

    /** Two wide columns per page (page 1 ≈ 48 lines: 24×2). */
    public const GRID_COLS = 2;

    /** idle | saving | saved — shown near toolbar entry */
    public string $draftFlash = '';

    /** Bumps when settings change so unit pickers re-render. */
    public int $unitsTick = 0;

    public string $search = '';

    /** draft | saved | closed — used by autosave / lock. */
    public string $persistStatus = 'draft';

    /** edit | view */
    public string $formMode = 'edit';

    #[On('tools-reorder-search')]
    public function updateSearch(string $query): void
    {
        $this->search = $query;
    }

    public function isViewMode(): bool
    {
        return $this->formMode === 'view' || $this->persistStatus === 'closed';
    }

    #[On('tools-reorder-action')]
    public function handleToolbarAction(string $action): void
    {
        if ($action === 'settings') {
            $this->dispatch(
                'open-form-panel',
                title: __('agricart.pages.tools.re_order_settings'),
                resource: __('agricart.pages.tools.re-order'),
                formKey: 'tools-reorder-settings',
                mode: 'edit',
            );

            return;
        }

        if ($action === 'new-order') {
            $this->startNewOrder();

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
            'add-page' => $this->addOrderPage(),
            'remove-page' => $this->removeOrderPage(),
            'save-close' => $this->saveAndClose(),
            'close-form' => $this->closeOrderForm(),
            'clear-all' => $this->clearAllOrder(),
            default => null,
        };
    }

    public function startNewOrder(): void
    {
        $this->authorizeCreate();

        // Never discard in-progress work — save first, then reopen draft.
        if ($this->screen === 'form' && ! $this->isViewMode()) {
            $this->autoSaveDraft(skipRender: false);
        }

        $draft = ToolsReorderOrder::query()
            ->where('user_id', auth()->id())
            ->where('status', 'draft')
            ->latest('updated_at')
            ->first();

        if ($draft) {
            $this->applyOrderRecord($draft);
            $this->persistStatus = 'draft';
            $this->formMode = 'edit';
            $this->screen = 'form';
            $this->draftFlash = 'recovered';
            $this->emitReorderScreen('form');
            Notification::make()
                ->title(__('agricart.pages.tools.re_order_draft_recovered'))
                ->success()
                ->send();

            return;
        }

        // Still typing on screen but draft row not written yet — keep it, don't wipe.
        if ($this->screen === 'form' && ! $this->isViewMode() && $this->draftHasContent()) {
            $this->autoSaveDraft(skipRender: false);
            $this->persistStatus = 'draft';
            $this->formMode = 'edit';
            $this->draftFlash = 'saved';
            $this->emitReorderScreen('form');
            Notification::make()
                ->title(__('agricart.pages.tools.re_order_draft_kept'))
                ->success()
                ->send();

            return;
        }

        $this->resetFreshOrder();
        $this->persistStatus = 'draft';
        $this->formMode = 'edit';
        $this->screen = 'form';
        $this->draftFlash = '';
        $this->emitReorderScreen('form');
        $this->js('window.dispatchEvent(new CustomEvent("tools-reorder-try-local-draft"))');
    }

    /**
     * Wipe the working sheet to zero. Only way to discard an unfinished draft.
     */
    public function clearAllOrder(): void
    {
        if ($this->isViewMode()) {
            return;
        }

        $this->authorizeCreate();

        ToolsReorderOrder::query()
            ->where('user_id', auth()->id())
            ->where('status', 'draft')
            ->delete();

        $this->resetFreshOrder();
        $this->persistStatus = 'draft';
        $this->formMode = 'edit';
        $this->screen = 'form';
        $this->draftFlash = '';
        $this->unitsTick++; // remount Alpine unit/phrase pickers (wire:ignore otherwise keeps old labels)
        $this->emitReorderScreen('form');
        $this->js('window.dispatchEvent(new CustomEvent("tools-reorder-clear-local-draft"))');

        Notification::make()
            ->title(__('agricart.pages.tools.re_order_cleared'))
            ->success()
            ->send();
    }

    public function openOrder(int $id): void
    {
        $order = ToolsReorderOrder::query()
            ->where('user_id', auth()->id())
            ->whereKey($id)
            ->firstOrFail();

        // Closed = read-only view. Open/draft = editable so lines and purchaser can change.
        $mode = (string) $order->status === 'closed' ? 'view' : 'edit';
        $this->loadOrderIntoForm($id, $mode);
    }

    public function editOrder(int $id): void
    {
        $order = ToolsReorderOrder::query()
            ->where('user_id', auth()->id())
            ->whereKey($id)
            ->firstOrFail();

        if ((string) $order->status === 'closed') {
            Notification::make()
                ->title(__('agricart.pages.tools.re_order_closed_locked'))
                ->warning()
                ->send();

            return;
        }

        $this->loadOrderIntoForm($id, 'edit');
    }

    public function closeListedOrder(int $id): void
    {
        $this->authorizeCreate();

        $order = ToolsReorderOrder::query()
            ->where('user_id', auth()->id())
            ->whereKey($id)
            ->where('status', 'saved')
            ->firstOrFail();

        $cells = is_array($order->cells) ? $order->cells : [];
        $unresolved = $this->unresolvedFilledLineCount($cells);

        if ($unresolved > 0) {
            Notification::make()
                ->title(__('agricart.pages.tools.re_order_close_lines_required', ['count' => $unresolved]))
                ->warning()
                ->send();

            return;
        }

        $forwarded = $this->forwardedCellsFrom($cells);

        $followUp = \Illuminate\Support\Facades\DB::transaction(function () use ($order, $forwarded) {
            $order->update(['status' => 'closed']);

            if ($forwarded === []) {
                return null;
            }

            return $this->createFollowUpOrderFrom($order, $forwarded);
        });

        if ($followUp === null) {
            Notification::make()
                ->title(__('agricart.pages.tools.re_order_closed_ok'))
                ->success()
                ->send();

            return;
        }

        Notification::make()
            ->title(__('agricart.pages.tools.re_order_closed_with_forward', [
                'count' => count($forwarded),
                'order' => $followUp->order_no,
            ]))
            ->success()
            ->send();

        // Open the new order in edit mode so more lines / purchaser can be added.
        $this->loadOrderIntoForm((int) $followUp->id, 'edit');
    }

    /**
     * Mark a filled line done / forward / clear (saved orders only; works in view mode).
     */
    public function markLineStatus(string $rowKey, string $status): void
    {
        $this->authorizeCreate();

        if ($this->persistStatus === 'closed') {
            return;
        }

        if ($this->persistStatus !== 'saved' && $this->persistStatus !== 'draft') {
            return;
        }

        if ($this->persistStatus !== 'saved') {
            return;
        }

        if (! in_array($status, ['done', 'forward', ''], true)) {
            return;
        }

        if (! ctype_digit($rowKey)) {
            return;
        }

        $cell = $this->normalizeCellValue($this->cells[$rowKey] ?? []);

        if (! $this->cellIsFilled($cell)) {
            return;
        }

        $cell['status'] = $status;
        $this->cells[$rowKey] = $cell;
        $this->persistSavedOrderCells();
    }

    public function canMarkLineStatus(): bool
    {
        return $this->persistStatus === 'saved';
    }

    public function unresolvedLineCount(): int
    {
        return $this->unresolvedFilledLineCount($this->cells);
    }

    public function deleteOrder(int $id): void
    {
        $this->authorizeCreate();

        $deleted = ToolsReorderOrder::query()
            ->where('user_id', auth()->id())
            ->whereKey($id)
            ->where('status', '!=', 'closed')
            ->delete();

        if ($deleted === 0) {
            Notification::make()
                ->title(__('agricart.pages.tools.re_order_closed_locked'))
                ->warning()
                ->send();
        }
    }

    protected function loadOrderIntoForm(int $id, string $mode): void
    {
        if ($mode === 'view') {
            $this->authorizeView();
        } else {
            $this->authorizeCreate();
        }

        $order = ToolsReorderOrder::query()
            ->where('user_id', auth()->id())
            ->whereKey($id)
            ->firstOrFail();

        $this->applyOrderRecord($order);
        $status = (string) $order->status;
        $this->persistStatus = in_array($status, ['saved', 'draft', 'closed'], true) ? $status : 'draft';
        $this->formMode = $mode === 'view' || $status === 'closed' ? 'view' : 'edit';
        $this->screen = 'form';
        $this->draftFlash = '';
        $this->unitsTick++;
        $this->emitReorderScreen('form');
    }

    /**
     * @return \Illuminate\Support\Collection<int, ToolsReorderOrder>
     */
    public function listedOrders()
    {
        $query = ToolsReorderOrder::query()
            ->where('user_id', auth()->id())
            ->whereIn('status', ['saved', 'closed'])
            ->latest('updated_at');

        $term = trim($this->search);
        if ($term !== '') {
            $like = '%'.$term.'%';
            $query->where(function ($q) use ($like): void {
                $q->where('order_no', 'like', $like)
                    ->orWhere('purchaser_name', 'like', $like)
                    ->orWhere('order_date', 'like', $like)
                    ->orWhere('prepared_by', 'like', $like)
                    ->orWhere('shop_name', 'like', $like);
            });
        }

        return $query->limit(150)->get();
    }

    /**
     * Apply browser localStorage draft when no DB draft exists.
     *
     * @param  array<string, mixed>  $payload
     */
    public function restoreLocalDraft(array $payload): void
    {
        if ($this->screen !== 'form' || $this->editingOrderId) {
            return;
        }

        if (! $this->draftLooksEmpty()) {
            return;
        }

        $this->applyLocalDraftPayload($payload);
        $this->draftFlash = 'recovered';
        Notification::make()
            ->title(__('agricart.pages.tools.re_order_draft_recovered'))
            ->success()
            ->send();
    }

    public function closeOrderForm(): void
    {
        if ($this->screen === 'form' && ! $this->isViewMode()) {
            $this->autoSaveDraft(skipRender: false);
        }

        $this->screen = 'list';
        $this->formMode = 'edit';
        $this->entryText = '';
        $this->forwardTo = '';
        $this->draftFlash = '';
        $this->emitReorderScreen('list');
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

        $orderNo = trim($this->orderNo);
        if ($orderNo === '') {
            $orderNo = app(ReOrderSettingsService::class)->peekOrderNumber();
            $this->orderNo = $orderNo;
        }

        $status = $this->persistStatus === 'saved' ? 'saved' : 'draft';
        $payload = $this->orderPayload($status);
        $payload['order_no'] = $orderNo;

        $order = ToolsReorderOrder::query()->updateOrCreate(
            [
                'user_id' => (int) auth()->id(),
                'order_no' => $orderNo,
            ],
            $payload,
        );

        $this->editingOrderId = (int) $order->id;
        $this->draftFlash = 'saved';

        if ($skipRender) {
            $this->skipRender();
        }
    }

    #[On('tools-reorder-settings-saved')]
    public function onSettingsSaved(): void
    {
        $this->unitsTick++;
    }

    protected function resetFreshOrder(): void
    {
        $user = auth()->user();
        $this->editingOrderId = null;
        $this->formLang = 'en';
        $this->orderNo = app(ReOrderSettingsService::class)->peekOrderNumber();
        $this->orderDate = now(config('app.display_timezone', config('app.timezone')))->format('d-m-Y');
        $this->purchaserKey = '';
        $this->preparedBy = (string) ($user?->name ?? '');
        $this->entryText = '';
        $this->forwardTo = '';
        $this->cells = [];
        $this->pendingNotes = [];
        $this->returnNotes = [];
        $this->pageCount = 1;
        $this->draftFlash = '';
    }

    protected function applyOrderRecord(ToolsReorderOrder $order): void
    {
        $this->editingOrderId = (int) $order->id;
        $this->formLang = in_array($order->lang, ['en', 'ur'], true) ? (string) $order->lang : 'en';
        $this->orderNo = (string) $order->order_no;
        $this->orderDate = (string) $order->order_date;
        $this->purchaserKey = $order->purchaser_index !== null ? (string) $order->purchaser_index : '';
        $this->preparedBy = (string) ($order->prepared_by ?? '');
        $this->entryText = '';
        $this->forwardTo = '';
        $this->cells = is_array($order->cells) ? $order->cells : [];
        $this->pendingNotes = [];
        $this->returnNotes = [];
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
        if (isset($payload['orderNo'])) {
            $this->orderNo = (string) $payload['orderNo'];
        }
        if (isset($payload['orderDate'])) {
            $this->orderDate = (string) $payload['orderDate'];
        }
        if (array_key_exists('purchaserKey', $payload)) {
            $this->purchaserKey = (string) ($payload['purchaserKey'] ?? '');
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
        if (is_array($payload['pendingNotes'] ?? null)) {
            $this->pendingNotes = $payload['pendingNotes'];
        }
        if (is_array($payload['returnNotes'] ?? null)) {
            $this->returnNotes = $payload['returnNotes'];
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
        return $this->purchaserKey === ''
            && trim($this->entryText) === ''
            && $this->normalizedCellsForSave() === []
            && $this->normalizedFootNotes($this->pendingNotes) === []
            && $this->normalizedFootNotes($this->returnNotes) === [];
    }

    protected function maxFilledRow(): int
    {
        $max = 0;
        foreach ($this->cells as $key => $value) {
            if (! ctype_digit((string) $key)) {
                continue;
            }
            $row = $this->normalizeCellValue($value);
            if ($row['detail'] === '' && $row['unit'] === '' && $row['qty'] === '' && $row['rate'] === '') {
                continue;
            }
            $max = max($max, (int) $key);
        }

        return $max;
    }

    /**
     * @return array<string, mixed>
     */
    protected function orderPayload(string $status): array
    {
        $purchaserName = '';
        $purchaserIndex = null;
        if ($this->purchaserKey !== '' && ctype_digit($this->purchaserKey)) {
            $purchaserIndex = (int) $this->purchaserKey;
            foreach ($this->purchaserOptions() as $option) {
                if ($option['key'] === $this->purchaserKey) {
                    $purchaserName = $option['label'];
                    break;
                }
            }
        }

        return [
            'user_id' => (int) auth()->id(),
            'order_date' => trim($this->orderDate),
            'lang' => $this->formLang,
            'shop_name' => $this->shopName(),
            'shop_address' => $this->shopAddress(),
            'shop_phones' => $this->shopPhones(),
            'purchaser_name' => $purchaserName,
            'purchaser_index' => $purchaserIndex,
            'prepared_by' => trim($this->preparedBy),
            'cells' => $this->cellsPayloadForSave(),
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
            'window.dispatchEvent(new CustomEvent("tools-reorder-lang-changed",{detail:{lang:'.json_encode($lang).'}}))'
        );
    }

    protected function emitReorderScreen(string $screen): void
    {
        $mode = $screen === 'form' ? $this->formMode : 'edit';
        $this->dispatch('tools-reorder-screen', screen: $screen, formMode: $mode);
        $this->js(
            'window.dispatchEvent(new CustomEvent("tools-reorder-screen",{detail:{screen:'.json_encode($screen).',formMode:'.json_encode($mode).'}}))'
        );
    }

    public function unitLabelFor(string $unitKey): string
    {
        if ($unitKey === '') {
            return '';
        }

        foreach ($this->unitOptions() as $option) {
            if ($option['key'] === $unitKey) {
                return $option['label'];
            }
        }

        return $unitKey;
    }

    public function purchaserLabel(): string
    {
        if ($this->purchaserKey === '') {
            return '';
        }

        foreach ($this->purchaserOptions() as $option) {
            if ($option['key'] === $this->purchaserKey) {
                return $option['label'];
            }
        }

        return '';
    }

    /**
     * @return list<string>
     */
    public function purchaserPhones(): array
    {
        if ($this->purchaserKey === '' || ! ctype_digit($this->purchaserKey)) {
            return [];
        }

        $s = $this->settingsData();
        $rows = is_array($s['purchasers'] ?? null) ? $s['purchasers'] : [];
        $index = (int) $this->purchaserKey;
        $row = $rows[$index] ?? null;
        if (! is_array($row)) {
            return [];
        }

        $phones = [];
        if (is_array($row['phones'] ?? null)) {
            foreach ($row['phones'] as $phone) {
                $phone = trim((string) $phone);
                if ($phone !== '' && ! in_array($phone, $phones, true)) {
                    $phones[] = $phone;
                }
            }
        } elseif (filled($row['phone'] ?? null)) {
            $phones[] = trim((string) $row['phone']);
        }

        return $phones;
    }

    public function clearEntry(): void
    {
        $this->entryText = '';
    }

    /**
     * Toolbar forward: push spoken/typed text into a numbered box, then clear the entry.
     */
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
                ->title(__('agricart.pages.tools.re_order_forward_need_text'))
                ->warning()
                ->send();

            return;
        }

        if ($to === '' || ! ctype_digit($to) || (int) $to < 1) {
            Notification::make()
                ->title(__('agricart.pages.tools.re_order_forward_need_box'))
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
        $row['detail'] = $text;
        $row['status'] = '';
        $this->cells[$rowKey] = $row;
        $this->entryText = '';
        $this->forwardTo = '';
        $this->rememberPhrase($text);
        $this->unitsTick++; // remount phrase/unit pickers so the target box shows new text
        $this->autoSaveDraft(skipRender: false);

        $this->js(
            'window.dispatchEvent(new CustomEvent("tools-reorder-entry-cleared"));'
            .'requestAnimationFrame(()=>{const el=document.querySelector(".agricart-ro-entry__input");if(el){el.focus();}});'
        );
    }

    public function updated(string $name, mixed $value): void
    {
        if (preg_match('/^cells\.(\d+)\.(detail|unit|qty|rate)$/', $name, $m) !== 1) {
            return;
        }

        $key = $m[1];
        $field = $m[2];
        $cell = $this->normalizeCellValue($this->cells[$key] ?? []);

        if ($field === 'rate') {
            $cell['rate'] = $this->encodeRateValue((string) $value);
        }

        // Editing line content clears fulfillment mark so it must be reviewed again.
        $cell['status'] = '';
        $this->cells[$key] = $cell;

        if ($field === 'detail') {
            $this->rememberPhrase((string) ($cell['detail'] ?? $value));
        }
    }

    /**
     * Save a typed phrase into the Re Order dictionary for later autocomplete.
     */
    public function rememberPhrase(string $text): void
    {
        if ($this->isViewMode()) {
            return;
        }

        $text = trim($text);
        if (mb_strlen($text) < 2) {
            return;
        }

        $phrases = app(ReOrderSettingsService::class)->rememberPhrase($text);
        $this->js(
            'window.__agricartRoPhrases = '.json_encode(array_values($phrases), JSON_UNESCAPED_UNICODE).';'
            .'window.dispatchEvent(new CustomEvent("tools-reorder-phrases-updated"));'
        );
    }

    /**
     * @return list<string>
     */
    public function phraseOptions(): array
    {
        return app(ReOrderSettingsService::class)->phrasesForUser();
    }

    /**
     * Normalize Indic digits, then map 0–9 through Tools → Re Order secret coding.
     * Already-coded letters stay; only plain digits are replaced (no double-encode).
     */
    protected function encodeRateValue(string $value): string
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
     * Sum qty × rate for lines that have both. Rates may be secret-coded.
     *
     * @return array{priced: int, total: float, plain: string, encoded: string}
     */
    public function orderAmountSummary(): array
    {
        $engine = app(SecretCodingEngine::class);
        $slots = $this->settingsData()['digitSlots'] ?? [];
        $slots = is_array($slots) ? $slots : [];
        $total = 0.0;
        $priced = 0;

        foreach ($this->cells as $key => $value) {
            if (! ctype_digit((string) $key) || str_starts_with((string) $key, '_')) {
                continue;
            }
            $row = $this->normalizeCellValue($value);
            if (! $this->cellIsFilled($row)) {
                continue;
            }

            $qtyRaw = trim(ReOrderSettingsForm::normalizeDigits($row['qty']));
            $rateRaw = trim(ReOrderSettingsForm::normalizeDigits($row['rate']));
            if ($qtyRaw === '' || $rateRaw === '') {
                continue;
            }

            $qty = $this->parseLooseNumber($qtyRaw);
            $rateDecoded = $slots !== []
                ? $engine->decodeWithSlots($rateRaw, $slots)
                : preg_replace('/[^\d.]/', '', $rateRaw);
            $rate = $this->parseLooseNumber((string) $rateDecoded);

            if ($qty <= 0 || $rate <= 0) {
                continue;
            }

            $total += $qty * $rate;
            $priced++;
        }

        $plain = $this->formatAmountNumber($total);
        $encoded = $plain === '' || $plain === '0'
            ? ''
            : ($slots !== [] ? $engine->encodeWithSlots($plain, $slots) : $plain);

        return [
            'priced' => $priced,
            'total' => $total,
            'plain' => $plain,
            'encoded' => $encoded,
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

        if (trim($this->purchaserKey) === '') {
            Notification::make()
                ->title(__('agricart.pages.tools.re_order_purchaser_required'))
                ->warning()
                ->send();

            return;
        }

        $this->authorizeCreate();

        $service = app(ReOrderSettingsService::class);
        $payload = $this->orderPayload('saved');
        $peeked = $service->peekOrderNumber();
        $orderNo = trim($this->orderNo);

        if ($orderNo === '' || $orderNo === $peeked) {
            $orderNo = $service->nextOrderNumber();
            $this->orderNo = $orderNo;
        }
        $payload['order_no'] = $orderNo;

        if ($this->editingOrderId) {
            $order = ToolsReorderOrder::query()
                ->where('user_id', auth()->id())
                ->whereKey($this->editingOrderId)
                ->firstOrFail();
            $order->update($payload);
        } else {
            ToolsReorderOrder::query()->updateOrCreate(
                [
                    'user_id' => (int) auth()->id(),
                    'order_no' => $payload['order_no'],
                ],
                $payload,
            );
        }

        $this->js('window.dispatchEvent(new CustomEvent("tools-reorder-clear-local-draft"))');
        $this->editingOrderId = null;
        $this->persistStatus = 'draft';
        $this->screen = 'list';
        $this->entryText = '';
        $this->forwardTo = '';
        $this->draftFlash = '';
        $this->cells = [];
        $this->pendingNotes = [];
        $this->returnNotes = [];
        $this->emitReorderScreen('list');
    }

    public function mount(): void
    {
        $this->normalizeCellsInPlace();
        $this->emitReorderScreen($this->screen);
    }

    public function hydrate(): void
    {
        $this->normalizeCellsInPlace();
    }

    protected function normalizeCellsInPlace(): void
    {
        if ($this->cells === []) {
            return;
        }

        $out = [];
        foreach ($this->cells as $key => $value) {
            $key = (string) $key;
            if ($key === '_pending' && is_array($value)) {
                $this->pendingNotes = $value;

                continue;
            }
            if ($key === '_returns' && is_array($value)) {
                $this->returnNotes = $value;

                continue;
            }
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
     * @return list<array{key: string, label: string}>
     */
    public function purchaserOptions(): array
    {
        $s = $this->settingsData();
        $rows = is_array($s['purchasers'] ?? null) ? $s['purchasers'] : [];
        $options = [];

        foreach ($rows as $i => $row) {
            if (! is_array($row)) {
                continue;
            }
            $en = trim((string) ($row['nameEn'] ?? ''));
            $ur = trim((string) ($row['nameUr'] ?? ''));
            $label = $this->formLang === 'ur'
                ? ($ur !== '' ? $ur : $en)
                : ($en !== '' ? $en : $ur);
            if ($label === '') {
                continue;
            }
            $options[] = [
                'key' => (string) $i,
                'label' => $label,
            ];
        }

        return $options;
    }

    /**
     * Units for searchable pickers (label follows form language).
     *
     * @return list<array{key: string, label: string, search: string}>
     */
    public function unitOptions(): array
    {
        $s = $this->settingsData();
        $rows = is_array($s['units'] ?? null) ? $s['units'] : [];
        $options = [];

        foreach ($rows as $i => $row) {
            if (is_string($row) || is_numeric($row)) {
                $name = trim((string) $row);
                if ($name === '') {
                    continue;
                }
                $options[] = [
                    'key' => (string) $i,
                    'label' => $name,
                    'search' => mb_strtolower($name),
                ];
                continue;
            }
            if (! is_array($row)) {
                continue;
            }
            $en = trim((string) ($row['nameEn'] ?? $row['name'] ?? ''));
            $ur = trim((string) ($row['nameUr'] ?? ''));
            $label = $this->formLang === 'ur'
                ? ($ur !== '' ? $ur : $en)
                : ($en !== '' ? $en : $ur);
            if ($label === '') {
                continue;
            }
            $options[] = [
                'key' => (string) $i,
                'label' => $label,
                'search' => mb_strtolower(trim($en.' '.$ur.' '.$label)),
            ];
        }

        return $options;
    }

    /**
     * @return array{detail: string, unit: string, qty: string, rate: string, status: string}
     */
    public function cellRow(string|int $rowNo): array
    {
        return $this->normalizeCellValue($this->cells[(string) $rowNo] ?? []);
    }

    /**
     * @return array{detail: string, unit: string, qty: string, rate: string, status: string}
     */
    protected function normalizeCellValue(mixed $value): array
    {
        if (is_string($value)) {
            return [
                'detail' => $value,
                'unit' => '',
                'qty' => '',
                'rate' => '',
                'status' => '',
            ];
        }

        if (! is_array($value)) {
            return [
                'detail' => '',
                'unit' => '',
                'qty' => '',
                'rate' => '',
                'status' => '',
            ];
        }

        $status = (string) ($value['status'] ?? '');
        if (! in_array($status, ['done', 'forward'], true)) {
            $status = '';
        }

        return [
            'detail' => (string) ($value['detail'] ?? ''),
            'unit' => (string) ($value['unit'] ?? ''),
            'qty' => (string) ($value['qty'] ?? ''),
            'rate' => (string) ($value['rate'] ?? ''),
            'status' => $status,
        ];
    }

    /**
     * @param  array{detail: string, unit: string, qty: string, rate: string, status: string}  $cell
     */
    protected function cellIsFilled(array $cell): bool
    {
        return $cell['detail'] !== '' || $cell['unit'] !== '' || $cell['qty'] !== '' || $cell['rate'] !== '';
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
     * @return list<array{detail: string, unit: string, qty: string, rate: string, status: string}>
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
     * @param  list<array{detail: string, unit: string, qty: string, rate: string, status: string}>  $forwarded
     */
    protected function createFollowUpOrderFrom(ToolsReorderOrder $source, array $forwarded): ToolsReorderOrder
    {
        $service = app(ReOrderSettingsService::class);
        $orderNo = $service->nextOrderNumber();
        $newCells = [];

        foreach (array_values($forwarded) as $i => $row) {
            $newCells[(string) ($i + 1)] = [
                'detail' => $row['detail'],
                'unit' => $row['unit'],
                'qty' => $row['qty'],
                'rate' => $row['rate'],
                'status' => '',
            ];
        }

        $pageCount = $this->pageCountForFilledRows(count($forwarded));
        $newCells['_pageCount'] = $pageCount;

        return ToolsReorderOrder::query()->create([
            'user_id' => (int) $source->user_id,
            'order_no' => $orderNo,
            'order_date' => now(config('app.display_timezone', config('app.timezone')))->format('d-m-Y'),
            'lang' => (string) ($source->lang ?: 'en'),
            'shop_name' => (string) ($source->shop_name ?? ''),
            'shop_address' => (string) ($source->shop_address ?? ''),
            'shop_phones' => is_array($source->shop_phones) ? $source->shop_phones : [],
            'purchaser_name' => (string) ($source->purchaser_name ?? ''),
            'purchaser_index' => $source->purchaser_index,
            'prepared_by' => (string) ($source->prepared_by ?? ''),
            'cells' => $newCells,
            // Draft so it opens writable (purchaser + more lines) before next Save & Close.
            'status' => 'draft',
        ]);
    }

    protected function persistSavedOrderCells(): void
    {
        if (! $this->editingOrderId || $this->persistStatus !== 'saved') {
            return;
        }

        $order = ToolsReorderOrder::query()
            ->where('user_id', auth()->id())
            ->whereKey($this->editingOrderId)
            ->where('status', 'saved')
            ->first();

        if (! $order) {
            return;
        }

        $order->update([
            'cells' => $this->cellsPayloadForSave(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function cellsPayloadForSave(): array
    {
        $out = $this->normalizedCellsForSave();
        $pending = $this->normalizedFootNotes($this->pendingNotes);
        $returns = $this->normalizedFootNotes($this->returnNotes);
        if ($pending !== []) {
            $out['_pending'] = $pending;
        }
        if ($returns !== []) {
            $out['_returns'] = $returns;
        }
        $out['_pageCount'] = max(1, min(20, $this->pageCount));

        return $out;
    }

    /**
     * @return array<string, array{detail: string, unit: string, qty: string, rate: string, status: string}>
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
     * @param  array<int|string, mixed>  $notes
     * @return array<string, list<string>>
     */
    protected function normalizedFootNotes(array $notes): array
    {
        $out = [];
        foreach ($notes as $page => $cells) {
            if (! is_array($cells)) {
                continue;
            }
            $row = [];
            for ($i = 0; $i < 4; $i++) {
                $row[] = trim((string) ($cells[$i] ?? ''));
            }
            if (implode('', $row) === '') {
                continue;
            }
            $out[(string) $page] = $row;
        }

        return $out;
    }


    public function addOrderPage(): void
    {
        if ($this->isViewMode()) {
            return;
        }
        $this->authorizeCreate();
        $this->pageCount = min(20, max(1, $this->pageCount + 1));
    }

    public function removeOrderPage(): void
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

    protected function authorizeView(): void
    {
        $user = auth()->user();
        abort_unless(
            $user && (
                $user->isSuperAdmin()
                || ModulePageAccess::can($user, 'tools', 're-order', 'view')
                || ModulePageAccess::can($user, 'tools', 're-order', 'create')
                || ModulePageAccess::can($user, 'tools', 're-order', 'edit')
            ),
            403
        );
    }

    protected function authorizeCreate(): void
    {
        $user = auth()->user();
        abort_unless(
            $user && ($user->isSuperAdmin() || ModulePageAccess::can($user, 'tools', 're-order', 'create')),
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
