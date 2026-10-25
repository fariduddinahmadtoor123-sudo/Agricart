<?php

namespace App\Filament\Clusters\Tools;

use App\Filament\Clusters\ToolsCluster;
use App\Filament\Pages\BaseModulePage;
use App\Models\ToolsReorderOrder;
use App\Services\Access\ModulePageAccess;
use App\Services\Tools\ReOrderSettingsService;
use BackedEnum;
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

    /** @var array<string, string> cell number => text (grid later) */
    public array $cells = [];

    public ?int $editingOrderId = null;

    /** Total A4 pages in this order (page 1 = full header, others = simple). */
    public int $pageCount = 1;

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
        }
    }

    public function startNewOrder(): void
    {
        $this->authorizeCreate();

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
        $this->pageCount = 1;
        $this->screen = 'form';
    }

    public function closeOrderForm(): void
    {
        $this->screen = 'list';
        $this->editingOrderId = null;
        $this->entryText = '';
        $this->forwardTo = '';
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
    }

    public function clearEntry(): void
    {
        $this->entryText = '';
    }

    public function forwardEntry(): void
    {
        $text = trim($this->entryText);
        $to = trim($this->forwardTo);

        if ($text === '' || $to === '' || ! ctype_digit($to)) {
            return;
        }

        $this->cells[$to] = $text;
        $this->entryText = '';
        // keep forwardTo for rapid sequential entry if desired — clear for clarity
        $this->forwardTo = '';
    }

    public function saveAndClose(): void
    {
        $this->authorizeCreate();

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

        $payload = [
            'user_id' => (int) auth()->id(),
            'order_no' => trim($this->orderNo) !== '' ? trim($this->orderNo) : app(ReOrderSettingsService::class)->nextOrderNumber(),
            'order_date' => trim($this->orderDate),
            'lang' => $this->formLang,
            'shop_name' => $this->shopName(),
            'shop_address' => $this->shopAddress(),
            'shop_phones' => $this->shopPhones(),
            'purchaser_name' => $purchaserName,
            'purchaser_index' => $purchaserIndex,
            'prepared_by' => trim($this->preparedBy),
            'cells' => $this->cells,
            'status' => 'saved',
        ];

        if ($this->editingOrderId) {
            $order = ToolsReorderOrder::query()
                ->where('user_id', auth()->id())
                ->whereKey($this->editingOrderId)
                ->firstOrFail();
            $order->update($payload);
        } else {
            // commit sequence when using peeked number
            $service = app(ReOrderSettingsService::class);
            $peeked = $service->peekOrderNumber();
            if (trim($this->orderNo) === '' || trim($this->orderNo) === $peeked) {
                $payload['order_no'] = $service->nextOrderNumber();
                $this->orderNo = $payload['order_no'];
            }
            ToolsReorderOrder::query()->updateOrCreate(
                [
                    'user_id' => (int) auth()->id(),
                    'order_no' => $payload['order_no'],
                ],
                $payload,
            );
        }

        $this->closeOrderForm();
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


    public function addOrderPage(): void
    {
        $this->authorizeCreate();
        $this->pageCount = min(20, max(1, $this->pageCount + 1));
    }

    public function removeOrderPage(): void
    {
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
