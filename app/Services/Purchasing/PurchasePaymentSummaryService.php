<?php

namespace App\Services\Purchasing;

use App\Models\PurchasePaymentSummary;
use App\Models\PurchasePaymentSummaryItem;
use App\Models\Supplier;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PurchasePaymentSummaryService
{
    public const PAPER_SIZES = ['a4', 'a5'];

    /** @var list<string> */
    public const LOCALES = ['en', 'ur'];

    /** @var list<string> */
    public const LINE_STATUSES = ['ok', 'dispute'];

    public function create(array $data): PurchasePaymentSummary
    {
        $validated = $this->validate($data);

        return DB::transaction(function () use ($validated): PurchasePaymentSummary {
            $items = $validated['items'];
            $paymentSources = $validated['payment_sources'];
            unset($validated['items'], $validated['payment_sources']);

            if (! filled($validated['summary_number'] ?? null)) {
                $validated['summary_number'] = $this->nextSummaryNumber();
            }

            $validated['created_by'] = $validated['created_by'] ?? auth()->id();
            $validated['payment_sources'] = $this->normalizePaymentSources($paymentSources);

            $summary = PurchasePaymentSummary::query()->create($validated);
            $this->syncItems($summary, $items);

            return $summary->fresh(['items', 'creator']);
        });
    }

    public function update(PurchasePaymentSummary $summary, array $data): PurchasePaymentSummary
    {
        $validated = $this->validate($data, $summary->id);
        $items = $validated['items'];
        $paymentSources = $validated['payment_sources'];
        unset($validated['items'], $validated['payment_sources']);

        return DB::transaction(function () use ($summary, $validated, $items, $paymentSources): PurchasePaymentSummary {
            $validated['payment_sources'] = $this->normalizePaymentSources($paymentSources);
            $summary->update($validated);
            $this->syncItems($summary, $items);

            return $summary->fresh(['items', 'creator']);
        });
    }

    public function delete(PurchasePaymentSummary $summary): void
    {
        $summary->delete();
    }

    public function nextSummaryNumber(): string
    {
        $next = (int) PurchasePaymentSummary::query()->max('id') + 1;

        do {
            $number = 'PPS-' . str_pad((string) $next, 5, '0', STR_PAD_LEFT);
            $next++;
        } while (PurchasePaymentSummary::query()->where('summary_number', $number)->exists());

        return $number;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function supplierRowsForNewForm(): array
    {
        return Supplier::query()
            ->where('status', 'active')
            ->orderBy('business_name_en')
            ->get()
            ->map(fn (Supplier $supplier): array => $this->rowFromSupplier($supplier))
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    public function rowFromSupplier(Supplier $supplier): array
    {
        $contact = $supplier->primaryContact();
        $addressParts = array_filter([
            filled($supplier->address_en) ? trim((string) $supplier->address_en) : null,
            filled($supplier->address_ur) && ! filled($supplier->address_en) ? trim((string) $supplier->address_ur) : null,
            $supplier->displayCity(),
        ]);

        return [
            'supplier_id' => $supplier->id,
            'is_manual' => false,
            'name_en' => (string) $supplier->business_name_en,
            'name_ur' => (string) ($supplier->business_name_ur ?? ''),
            'phone' => (string) (is_array($contact) ? ($contact['mobile_number'] ?? '') : ''),
            'address' => implode(', ', $addressParts),
            'payment_amount' => '',
            'line_status' => '',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function emptyManualRow(): array
    {
        return [
            'supplier_id' => null,
            'is_manual' => true,
            'name_en' => '',
            'name_ur' => '',
            'phone' => '',
            'address' => '',
            'payment_amount' => '',
            'line_status' => '',
        ];
    }

    /**
     * @return list<array{label: string, amount: string}>
     */
    public function defaultPaymentSources(): array
    {
        return [
            ['label' => '', 'amount' => ''],
            ['label' => '', 'amount' => ''],
            ['label' => '', 'amount' => ''],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toFormState(PurchasePaymentSummary $summary): array
    {
        $summary->loadMissing('items');

        return [
            'summary_number' => $summary->summary_number,
            'summary_date' => $summary->summary_date?->format('Y-m-d') ?? now()->format('Y-m-d'),
            'paper_size' => $summary->paper_size,
            'locale' => $summary->displayLocale(),
            'notes' => (string) ($summary->notes ?? ''),
            'payment_sources' => $this->normalizePaymentSources($summary->payment_sources ?? []),
            'rows' => $summary->items->map(fn (PurchasePaymentSummaryItem $item): array => [
                'supplier_id' => $item->supplier_id,
                'is_manual' => (bool) $item->is_manual,
                'name_en' => (string) $item->name_en,
                'name_ur' => (string) ($item->name_ur ?? ''),
                'phone' => (string) ($item->phone ?? ''),
                'address' => (string) ($item->address ?? ''),
                'payment_amount' => (string) ($item->payment_amount ?? ''),
                'line_status' => (string) ($item->line_status ?? ''),
            ])->all(),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function validate(array $data, ?int $ignoreId = null): array
    {
        $validated = validator($data, [
            'summary_number' => [
                'nullable',
                'string',
                'max:32',
                Rule::unique('purchase_payment_summaries', 'summary_number')->ignore($ignoreId),
            ],
            'summary_date' => ['required', 'date'],
            'paper_size' => ['required', Rule::in(self::PAPER_SIZES)],
            'locale' => ['required', Rule::in(self::LOCALES)],
            'notes' => ['nullable', 'string', 'max:5000'],
            'payment_sources' => ['nullable', 'array', 'max:3'],
            'payment_sources.*.label' => ['nullable', 'string', 'max:120'],
            'payment_sources.*.amount' => ['nullable', 'string', 'max:32'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.supplier_id' => ['nullable', 'integer', 'exists:suppliers,id'],
            'items.*.is_manual' => ['nullable', 'boolean'],
            'items.*.name_en' => ['nullable', 'string', 'max:255'],
            'items.*.name_ur' => ['nullable', 'string', 'max:255'],
            'items.*.phone' => ['nullable', 'string', 'max:64'],
            'items.*.address' => ['nullable', 'string', 'max:500'],
            'items.*.payment_amount' => ['nullable', 'string', 'max:32'],
            'items.*.line_status' => ['nullable', Rule::in([...self::LINE_STATUSES, ''])],
            'created_by' => ['nullable', 'integer', 'exists:users,id'],
        ])->validate();

        $items = $this->normalizeItems($validated['items'] ?? []);

        if ($items === []) {
            throw ValidationException::withMessages([
                'items' => __('purchase_payment_summary.validation.items_required'),
            ]);
        }

        if (! $this->hasMeaningfulContent($items, $validated['payment_sources'] ?? [])) {
            throw ValidationException::withMessages([
                'items' => __('purchase_payment_summary.validation.content_required'),
            ]);
        }

        $validated['items'] = $items;

        return $validated;
    }

    /**
     * @param  list<array<string, mixed>>  $items
     * @param  list<array<string, mixed>>  $paymentSources
     */
    private function hasMeaningfulContent(array $items, array $paymentSources): bool
    {
        foreach ($items as $item) {
            if (filled($item['payment_amount'] ?? null)) {
                return true;
            }
        }

        foreach ($paymentSources as $source) {
            if (filled($source['amount'] ?? null)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  list<array<string, mixed>>  $items
     * @return list<array<string, mixed>>
     */
    private function normalizeItems(array $items): array
    {
        $normalized = [];

        foreach ($items as $item) {
            $isManual = (bool) ($item['is_manual'] ?? false);
            $nameEn = trim((string) ($item['name_en'] ?? ''));
            $nameUr = trim((string) ($item['name_ur'] ?? ''));
            $payment = trim((string) ($item['payment_amount'] ?? ''));
            $lineStatus = trim((string) ($item['line_status'] ?? ''));

            if (! $isManual && ! filled($item['supplier_id'] ?? null) && $nameEn === '' && $nameUr === '' && $payment === '') {
                continue;
            }

            if ($isManual && $nameEn === '' && $nameUr === '' && $payment === '') {
                continue;
            }

            $normalized[] = [
                'supplier_id' => filled($item['supplier_id'] ?? null) ? (int) $item['supplier_id'] : null,
                'is_manual' => $isManual,
                'name_en' => $nameEn !== '' ? $nameEn : ($nameUr !== '' ? $nameUr : '—'),
                'name_ur' => $nameUr,
                'phone' => trim((string) ($item['phone'] ?? '')),
                'address' => trim((string) ($item['address'] ?? '')),
                'payment_amount' => $payment !== '' ? $payment : null,
                'line_status' => in_array($lineStatus, self::LINE_STATUSES, true) ? $lineStatus : null,
            ];
        }

        return $normalized;
    }

    /**
     * @param  list<array<string, mixed>>  $items
     */
    private function syncItems(PurchasePaymentSummary $summary, array $items): void
    {
        $summary->items()->delete();

        foreach ($items as $index => $item) {
            $summary->items()->create([
                ...$item,
                'sort_order' => $index + 1,
            ]);
        }
    }

    /**
     * @param  array<int, mixed>|null  $sources
     * @return list<array{label: string, amount: string}>
     */
    private function normalizePaymentSources(?array $sources): array
    {
        $normalized = [];

        foreach ($this->defaultPaymentSources() as $index => $default) {
            $source = is_array($sources[$index] ?? null) ? $sources[$index] : [];
            $normalized[] = [
                'label' => trim((string) ($source['label'] ?? '')),
                'amount' => trim((string) ($source['amount'] ?? '')),
            ];
        }

        return $normalized;
    }
}
