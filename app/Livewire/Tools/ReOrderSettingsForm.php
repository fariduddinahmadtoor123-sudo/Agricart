<?php

namespace App\Livewire\Tools;

use App\Livewire\Concerns\AuthorizesSensitiveActions;
use App\Services\Tools\ReOrderSettingsService;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class ReOrderSettingsForm extends Component
{
    use AuthorizesSensitiveActions;

    public string $shopNameEn = '';

    public string $shopNameUr = '';

    /** @var list<string> */
    public array $phones = [''];

    public string $addressEn = '';

    public string $addressUr = '';

    /** @var list<array{nameEn: string, nameUr: string, phones: list<string>}> */
    public array $purchasers = [];

    public string $draftPurchaserEn = '';

    public string $draftPurchaserUr = '';

    /** @var list<string> */
    public array $draftPurchaserPhones = [''];

    /** @var array<string, string> */
    public array $digitSlots = [];

    public bool $saving = false;

    public ?string $statusMessage = null;

    public string $statusType = 'success';

    public const DIGIT_ORDER = ['1', '2', '3', '4', '5', '6', '7', '8', '9', '0'];

    public function mount(): void
    {
        if (! $this->canAccessAny(['tools.re-order.view', 'tools.re-order.edit', 'tools.re-order.create'])) {
            return;
        }

        $this->digitSlots = self::emptyDigitSlots();
        $this->loadSettings();
    }

    /**
     * @return array<string, string>
     */
    public static function emptyDigitSlots(): array
    {
        return array_fill_keys(self::DIGIT_ORDER, '');
    }

    public function openForm(): void
    {
        $this->authorizeAny(['tools.re-order.view', 'tools.re-order.edit', 'tools.re-order.create']);
        $this->statusMessage = null;
        $this->loadSettings();
    }

    public function handlePanelAction(string $action): void
    {
        $this->authorizeAny(['tools.re-order.edit', 'tools.re-order.create']);

        match ($action) {
            'save_close', 'save_next' => $this->saveAndClose(),
            default => null,
        };
    }

    public function addPhone(): void
    {
        $this->authorizeAny(['tools.re-order.edit', 'tools.re-order.create']);
        $this->phones[] = '';
    }

    public function removePhone(int $index): void
    {
        $this->authorizeAny(['tools.re-order.edit', 'tools.re-order.create']);

        if (count($this->phones) <= 1) {
            $this->phones = [''];

            return;
        }

        unset($this->phones[$index]);
        $this->phones = array_values($this->phones);
    }

    public function addDraftPurchaserPhone(): void
    {
        $this->authorizeAny(['tools.re-order.edit', 'tools.re-order.create']);
        $this->draftPurchaserPhones[] = '';
    }

    public function removeDraftPurchaserPhone(int $index): void
    {
        $this->authorizeAny(['tools.re-order.edit', 'tools.re-order.create']);

        if (count($this->draftPurchaserPhones) <= 1) {
            $this->draftPurchaserPhones = [''];

            return;
        }

        unset($this->draftPurchaserPhones[$index]);
        $this->draftPurchaserPhones = array_values($this->draftPurchaserPhones);
    }

    public function addPurchaser(): void
    {
        $this->authorizeAny(['tools.re-order.edit', 'tools.re-order.create']);

        $nameEn = trim($this->draftPurchaserEn);
        $nameUr = trim($this->draftPurchaserUr);
        $phones = $this->cleanPhoneList($this->draftPurchaserPhones);

        if ($nameEn === '' && $nameUr === '') {
            return;
        }

        foreach ($this->purchasers as $existing) {
            $sameEn = $nameEn !== '' && mb_strtolower((string) ($existing['nameEn'] ?? '')) === mb_strtolower($nameEn);
            $sameUr = $nameUr !== '' && mb_strtolower((string) ($existing['nameUr'] ?? '')) === mb_strtolower($nameUr);
            if ($sameEn || $sameUr) {
                $this->clearDraftPurchaser();

                return;
            }
        }

        $this->purchasers[] = [
            'nameEn' => $nameEn,
            'nameUr' => $nameUr,
            'phones' => $phones,
        ];
        $this->clearDraftPurchaser();
        $this->persist();
    }

    public function removePurchaser(int $index): void
    {
        $this->authorizeAny(['tools.re-order.edit', 'tools.re-order.create']);

        if (! array_key_exists($index, $this->purchasers)) {
            return;
        }

        unset($this->purchasers[$index]);
        $this->purchasers = array_values($this->purchasers);
        $this->persist();
    }

    protected function clearDraftPurchaser(): void
    {
        $this->draftPurchaserEn = '';
        $this->draftPurchaserUr = '';
        $this->draftPurchaserPhones = [''];
    }

    public function updated(string $name, mixed $value): void
    {
        if (str_starts_with($name, 'phones.')) {
            $index = (int) substr($name, strlen('phones.'));
            if (array_key_exists($index, $this->phones)) {
                $this->phones[$index] = self::normalizeDigits((string) $value);
            }

            return;
        }

        if (str_starts_with($name, 'draftPurchaserPhones.')) {
            $index = (int) substr($name, strlen('draftPurchaserPhones.'));
            if (array_key_exists($index, $this->draftPurchaserPhones)) {
                $this->draftPurchaserPhones[$index] = self::normalizeDigits((string) $value);
            }
        }
    }

    public function saveAndClose(): void
    {
        $this->authorizeAny(['tools.re-order.edit', 'tools.re-order.create']);

        if (trim($this->draftPurchaserEn) !== '' || trim($this->draftPurchaserUr) !== '') {
            $this->addPurchaser();
        }

        if ($this->persist()) {
            $this->statusMessage = __('agricart.pages.tools.re_order_settings_saved');
            $this->statusType = 'success';
            $this->dispatch('close-form-panel');
        }
    }

    public function persist(): bool
    {
        $this->saving = true;
        $this->dispatchBusy(true);

        try {
            $phones = $this->cleanPhoneList($this->phones);
            $this->phones = $phones === [] ? [''] : $phones;

            $slots = [];
            foreach (self::DIGIT_ORDER as $digit) {
                $slots[$digit] = trim((string) ($this->digitSlots[$digit] ?? ''));
            }

            $payload = [
                'shopNameEn' => trim($this->shopNameEn),
                'shopNameUr' => trim($this->shopNameUr),
                'phones' => $phones,
                'phone' => $phones[0] ?? '',
                'addressEn' => trim($this->addressEn),
                'addressUr' => trim($this->addressUr),
                'purchasers' => collect($this->purchasers)
                    ->map(function (array $row): ?array {
                        $nameEn = trim((string) ($row['nameEn'] ?? ''));
                        $nameUr = trim((string) ($row['nameUr'] ?? ''));
                        $phones = $this->cleanPhoneList($row['phones'] ?? ($row['phone'] ?? []));

                        if ($nameEn === '' && $nameUr === '') {
                            return null;
                        }

                        return [
                            'nameEn' => $nameEn,
                            'nameUr' => $nameUr,
                            'phones' => $phones,
                            'phone' => $phones[0] ?? '',
                        ];
                    })
                    ->filter()
                    ->values()
                    ->all(),
                'digitSlots' => $slots,
            ];

            app(ReOrderSettingsService::class)->saveForUser($payload);
            $this->dispatch('tools-reorder-settings-saved', settings: $payload);

            return true;
        } finally {
            $this->saving = false;
            $this->dispatchBusy(false);
        }
    }

    /**
     * @param  list<string>|string  $phones
     * @return list<string>
     */
    protected function cleanPhoneList(array|string $phones): array
    {
        if (is_string($phones)) {
            $phones = [$phones];
        }

        $clean = [];
        foreach ($phones as $phone) {
            $phone = self::normalizeDigits(trim((string) $phone));
            if ($phone !== '' && ! in_array($phone, $clean, true)) {
                $clean[] = $phone;
            }
        }

        return array_values($clean);
    }

    protected function loadSettings(): void
    {
        $data = app(ReOrderSettingsService::class)->getForUser();

        $this->shopNameEn = (string) ($data['shopNameEn'] ?? '');
        $this->shopNameUr = (string) ($data['shopNameUr'] ?? '');
        $this->addressEn = (string) ($data['addressEn'] ?? $data['address'] ?? '');
        $this->addressUr = (string) ($data['addressUr'] ?? '');

        $savedPhones = [];
        if (is_array($data['phones'] ?? null)) {
            $savedPhones = $this->cleanPhoneList($data['phones']);
        } elseif (filled($data['phone'] ?? null)) {
            $savedPhones = $this->cleanPhoneList([(string) $data['phone']]);
        }
        $this->phones = $savedPhones === [] ? [''] : $savedPhones;

        $this->digitSlots = self::emptyDigitSlots();
        $savedSlots = is_array($data['digitSlots'] ?? null) ? $data['digitSlots'] : [];
        foreach (self::DIGIT_ORDER as $digit) {
            $this->digitSlots[$digit] = (string) ($savedSlots[$digit] ?? '');
        }

        $rows = is_array($data['purchasers'] ?? null) ? $data['purchasers'] : [];
        $this->purchasers = [];
        foreach ($rows as $row) {
            if (is_string($row) || is_numeric($row)) {
                $name = trim((string) $row);
                if ($name === '') {
                    continue;
                }
                $this->purchasers[] = [
                    'nameEn' => $name,
                    'nameUr' => '',
                    'phones' => [],
                ];
                continue;
            }
            if (! is_array($row)) {
                continue;
            }
            $nameEn = trim((string) ($row['nameEn'] ?? $row['name'] ?? ''));
            $nameUr = trim((string) ($row['nameUr'] ?? ''));
            if (isset($row['phones'])) {
                $phones = $this->cleanPhoneList($row['phones']);
            } else {
                $phones = $this->cleanPhoneList([(string) ($row['phone'] ?? '')]);
            }
            if ($nameEn === '' && $nameUr === '') {
                continue;
            }
            $this->purchasers[] = [
                'nameEn' => $nameEn,
                'nameUr' => $nameUr,
                'phones' => $phones,
            ];
        }
        $this->clearDraftPurchaser();
    }

    protected function dispatchBusy(bool $saving): void
    {
        $this->dispatch('tools-reorder-settings-form-busy', saving: $saving);
    }

    public static function normalizeDigits(string $value): string
    {
        $map = [
            '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
            '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
            '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
            '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
            '०' => '0', '१' => '1', '२' => '2', '३' => '3', '४' => '4',
            '५' => '5', '६' => '6', '७' => '7', '८' => '8', '९' => '9',
        ];

        return strtr($value, $map);
    }

    public function render(): View
    {
        return view('livewire.tools.re-order-settings-form');
    }
}
