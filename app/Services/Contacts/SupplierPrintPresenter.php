<?php

namespace App\Services\Contacts;

use App\Filament\Forms\CategoryForm;
use App\Models\Supplier;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

class SupplierPrintPresenter
{
    /**
     * @param  Collection<int, Supplier>  $suppliers
     * @return list<array<string, mixed>>
     */
    public function cards(Collection $suppliers): array
    {
        $categoryIds = $suppliers
            ->flatMap(fn (Supplier $supplier): array => $supplier->categories->pluck('id')->all())
            ->unique()
            ->values()
            ->all();
        $categoryLabels = CategoryForm::categoryLabelsByIds($categoryIds);

        return $suppliers
            ->map(fn (Supplier $supplier): array => $this->card($supplier, $categoryLabels))
            ->values()
            ->all();
    }

    /**
     * @param  array<int, string>  $categoryLabels
     * @return array<string, mixed>
     */
    public function card(Supplier $supplier, array $categoryLabels = []): array
    {
        $facts = [];
        $this->pushFact($facts, __('suppliers.fields.supplier_code'), $supplier->supplier_code);
        $this->pushFact($facts, __('suppliers.list.col_status'), __('suppliers.statuses.'.$supplier->status));

        $type = $supplier->supplierTypeLabel();
        if ($type !== '—') {
            $this->pushFact($facts, __('suppliers.fields.supplier_type'), $type);
        }

        $this->pushFact($facts, __('suppliers.fields.business_name_en'), $supplier->business_name_en);
        $this->pushFact($facts, __('suppliers.fields.business_name_ur'), $supplier->business_name_ur);

        $country = $this->countryLabel((string) $supplier->country_code, (string) ($supplier->country_ur ?? ''));
        $this->pushFact($facts, __('suppliers.fields.country'), $country);
        $this->pushFact($facts, __('suppliers.fields.city'), $supplier->displayCity());
        $this->pushFact($facts, __('suppliers.fields.address_en'), $supplier->address_en);
        $this->pushFact($facts, __('suppliers.fields.address_ur'), $supplier->address_ur);

        if ($supplier->credit_limit !== null) {
            $this->pushFact($facts, __('suppliers.fields.credit_limit'), number_format((float) $supplier->credit_limit, 2));
        }

        $this->pushFact($facts, __('suppliers.fields.opening_balance'), number_format((float) $supplier->opening_balance, 2));

        $categoryNames = $supplier->categories
            ->map(fn ($category) => $categoryLabels[$category->id] ?? $category->name_en)
            ->filter()
            ->values()
            ->all();

        if ($categoryNames !== []) {
            $this->pushFact($facts, __('suppliers.fields.categories'), implode(', ', $categoryNames));
        }

        $this->pushFact($facts, __('suppliers.fields.email'), $supplier->email);

        if (filled($supplier->custom_categories)) {
            $this->pushFact($facts, __('agricart.supplier_register.fields.custom_categories'), (string) $supplier->custom_categories);
        }

        if (filled($supplier->application_note)) {
            $this->pushFact($facts, __('suppliers.print.application_note'), (string) $supplier->application_note);
        }

        return [
            'code' => (string) $supplier->supplier_code,
            'name' => $supplier->displayBusinessName(),
            'photoUrl' => $this->publicUrl($supplier->profile_photo_path),
            'cardFrontUrl' => $this->publicUrl($supplier->card_front_path),
            'cardBackUrl' => $this->publicUrl($supplier->card_back_path),
            'facts' => $facts,
            'contacts' => $this->contacts($supplier),
            'banks' => $this->banks($supplier),
        ];
    }

    /**
     * @param  list<array{label: string, value: string}>  $facts
     */
    private function pushFact(array &$facts, string $label, mixed $value): void
    {
        $text = trim((string) ($value ?? ''));

        if ($text === '' || $text === '—') {
            return;
        }

        $facts[] = ['label' => $label, 'value' => $text];
    }

    /**
     * @return list<array{name: string, mobile: string}>
     */
    private function contacts(Supplier $supplier): array
    {
        $rows = [];

        foreach (is_array($supplier->contacts) ? $supplier->contacts : [] as $contact) {
            if (! is_array($contact)) {
                continue;
            }

            $locale = app()->getLocale();
            $ur = trim((string) ($contact['contact_name_ur'] ?? ''));
            $en = trim((string) ($contact['contact_name_en'] ?? ''));
            $name = ($locale === 'ur' && $ur !== '') ? $ur : ($en !== '' ? $en : $ur);
            $mobile = trim((string) ($contact['mobile_number'] ?? ''));

            if ($name === '' && $mobile === '') {
                continue;
            }

            $rows[] = [
                'name' => $name !== '' ? $name : '—',
                'mobile' => $mobile,
            ];
        }

        return $rows;
    }

    /**
     * @return list<array{bank: string, title: string, number: string}>
     */
    private function banks(Supplier $supplier): array
    {
        $rows = [];
        $locale = app()->getLocale();

        foreach (is_array($supplier->bank_accounts) ? $supplier->bank_accounts : [] as $account) {
            if (! is_array($account)) {
                continue;
            }

            $bankUr = trim((string) ($account['bank_name_ur'] ?? ''));
            $bankEn = trim((string) ($account['bank_name_en'] ?? ''));
            $titleUr = trim((string) ($account['account_title_ur'] ?? ''));
            $titleEn = trim((string) ($account['account_title'] ?? ''));
            $number = trim((string) ($account['account_number'] ?? ''));
            $bank = ($locale === 'ur' && $bankUr !== '') ? $bankUr : ($bankEn !== '' ? $bankEn : $bankUr);
            $title = ($locale === 'ur' && $titleUr !== '') ? $titleUr : ($titleEn !== '' ? $titleEn : $titleUr);

            if ($bank === '' && $title === '' && $number === '') {
                continue;
            }

            $rows[] = [
                'bank' => $bank,
                'title' => $title,
                'number' => $number,
            ];
        }

        return $rows;
    }

    private function countryLabel(string $code, string $countryUr): string
    {
        $locale = app()->getLocale();
        $row = config('countries.'.$code);

        if (is_array($row)) {
            if ($locale === 'ur') {
                return (string) ($row['ur'] ?? $row['en'] ?? $code);
            }

            return (string) ($row['en'] ?? $code);
        }

        if ($locale === 'ur' && $countryUr !== '') {
            return $countryUr;
        }

        return $code;
    }

    private function publicUrl(?string $path): ?string
    {
        if (! filled($path) || ! Storage::disk('public')->exists($path)) {
            return null;
        }

        return Storage::disk('public')->url($path);
    }
}
