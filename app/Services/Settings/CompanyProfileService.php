<?php

namespace App\Services\Settings;

use App\Models\CompanyProfile;
use App\Models\StoreSetting;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class CompanyProfileService
{
    public function current(): CompanyProfile
    {
        $profile = CompanyProfile::current();
        $this->hydrateFromStorefrontIfEmpty();

        return $profile->fresh() ?? $profile;
    }

    public function isConfigured(): bool
    {
        return $this->current()->isConfigured();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function save(array $data, TemporaryUploadedFile|UploadedFile|null $logoUpload = null): CompanyProfile
    {
        $profile = CompanyProfile::current();

        if ($logoUpload !== null) {
            $path = app(\App\Services\Media\OptimizedImageStorage::class)->store($logoUpload, 'store');

            if (filled($profile->logo_path) && $profile->logo_path !== $path) {
                Storage::disk('public')->delete($profile->logo_path);
            }

            $data['logo_path'] = $path;
        }

        if (array_key_exists('phones', $data)) {
            $data['phones'] = $this->normalizePhones(is_array($data['phones']) ? $data['phones'] : []);
        }

        if (array_key_exists('addresses', $data)) {
            $data['addresses'] = $this->normalizeAddresses(is_array($data['addresses']) ? $data['addresses'] : []);
        }

        if (array_key_exists('whatsapp_number', $data)) {
            $data['whatsapp_number'] = $this->normalizeWhatsAppNumber($data['whatsapp_number']);
        }

        $profile->fill($data);
        $profile->save();

        $this->syncToStorefront($profile);

        return $profile->fresh() ?? $profile;
    }

    public function removeLogo(): CompanyProfile
    {
        $profile = CompanyProfile::current();

        if (filled($profile->logo_path)) {
            Storage::disk('public')->delete($profile->logo_path);
            $profile->update(['logo_path' => null]);
        }

        return $profile->fresh() ?? $profile;
    }

    public function syncFromStorefront(): CompanyProfile
    {
        $profile = CompanyProfile::current();
        $store = StoreSetting::current();
        $updates = [];

        if (! filled($profile->business_name_en) && filled($store->site_name_en)) {
            $updates['business_name_en'] = $store->site_name_en;
        }

        if (! filled($profile->business_name_ur) && filled($store->site_name_ur)) {
            $updates['business_name_ur'] = $store->site_name_ur;
        }

        if (! filled($profile->whatsapp_number) && filled($store->whatsapp_number)) {
            $updates['whatsapp_number'] = $store->whatsapp_number;
        }

        if ($updates !== []) {
            $profile->update($updates);
        }

        return $profile->fresh() ?? $profile;
    }

    private function hydrateFromStorefrontIfEmpty(): void
    {
        $this->syncFromStorefront();
    }

    private function syncToStorefront(CompanyProfile $profile): void
    {
        $store = StoreSetting::current();
        $updates = [];

        if (filled($profile->business_name_en)) {
            $updates['site_name_en'] = $profile->business_name_en;
        }

        if (filled($profile->business_name_ur)) {
            $updates['site_name_ur'] = $profile->business_name_ur;
        }

        $updates['whatsapp_number'] = filled($profile->whatsapp_number)
            ? $profile->whatsapp_number
            : null;

        $store->update($updates);
        app(\App\Services\Store\StoreLayoutService::class)->clearCache();
    }

    /**
     * @param  list<array<string, mixed>>  $phones
     * @return list<array<string, mixed>>
     */
    public function normalizePhones(array $phones): array
    {
        $normalized = [];
        $hasPrimary = false;

        foreach ($phones as $row) {
            if (! is_array($row) || ! filled($row['phone_number'] ?? null)) {
                continue;
            }

            $isPrimary = ! empty($row['is_primary']);

            if ($isPrimary && $hasPrimary) {
                $isPrimary = false;
            }

            if ($isPrimary) {
                $hasPrimary = true;
            }

            $normalized[] = [
                'designation' => $this->normalizeDesignation((string) ($row['designation'] ?? 'general')),
                'contact_person_en' => trim((string) ($row['contact_person_en'] ?? '')),
                'contact_person_ur' => trim((string) ($row['contact_person_ur'] ?? '')),
                'phone_number' => trim((string) $row['phone_number']),
                'is_primary' => $isPrimary,
            ];
        }

        if ($normalized !== [] && ! $hasPrimary) {
            $normalized[0]['is_primary'] = true;
        }

        return $normalized;
    }

    public function normalizeWhatsAppNumber(mixed $number): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $number);

        if (! is_string($digits) || $digits === '') {
            return null;
        }

        if (strlen($digits) === 11 && str_starts_with($digits, '0')) {
            return '92'.substr($digits, 1);
        }

        if (strlen($digits) === 10 && str_starts_with($digits, '3')) {
            return '92'.$digits;
        }

        return $digits;
    }

    /**
     * @param  list<array<string, mixed>>  $addresses
     * @return list<array<string, mixed>>
     */
    public function normalizeAddresses(array $addresses): array
    {
        $normalized = [];
        $hasPrimary = false;

        foreach ($addresses as $row) {
            if (! is_array($row)) {
                continue;
            }

            $addressEn = trim((string) ($row['address_en'] ?? ''));
            $addressUr = trim((string) ($row['address_ur'] ?? ''));

            if ($addressEn === '' && $addressUr === '') {
                $addressEn = $this->composeLegacyAddress($row, 'en');
                $addressUr = $this->composeLegacyAddress($row, 'ur');
            }

            if ($addressEn === '' && $addressUr === '') {
                continue;
            }

            $isPrimary = ! empty($row['is_primary']);

            if ($isPrimary && $hasPrimary) {
                $isPrimary = false;
            }

            if ($isPrimary) {
                $hasPrimary = true;
            }

            $normalized[] = [
                'address_en' => $addressEn,
                'address_ur' => $addressUr,
                'postal_code' => trim((string) ($row['postal_code'] ?? '')),
                'is_primary' => $isPrimary,
            ];
        }

        if ($normalized !== [] && ! $hasPrimary) {
            $normalized[0]['is_primary'] = true;
        }

        return $normalized;
    }

    /**
     * @return array<string, mixed>
     */
    public function toFormState(CompanyProfile $profile): array
    {
        $phones = is_array($profile->phones) && $profile->phones !== []
            ? array_map(fn (array $row): array => $this->migratePhoneRow($row), $profile->phones)
            : [$this->emptyPhoneRow()];

        $addresses = is_array($profile->addresses) && $profile->addresses !== []
            ? array_map(fn (array $row): array => $this->migrateAddressRow($row), $profile->addresses)
            : [$this->emptyAddressRow()];

        return [
            'business_name_en' => $profile->business_name_en ?? '',
            'business_name_ur' => $profile->business_name_ur ?? '',
            'tagline_en' => $profile->tagline_en ?? '',
            'tagline_ur' => $profile->tagline_ur ?? '',
            'email' => $profile->email ?? '',
            'whatsapp_number' => $profile->whatsapp_number ?? '',
            'website' => $profile->website ?? '',
            'ntn' => $profile->ntn ?? '',
            'strn' => $profile->strn ?? '',
            'currency_code' => $profile->currency_code ?? 'PKR',
            'price_use_decimals' => (bool) $profile->price_use_decimals,
            'price_decimal_places' => (int) ($profile->price_decimal_places ?? 2),
            'phones' => $phones,
            'addresses' => $addresses,
            'logo_path' => $profile->logo_path,
            'ai_status' => $profile->ai_status?->value ?? 'none',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function emptyPhoneRow(): array
    {
        return [
            'designation' => 'general',
            'contact_person_en' => '',
            'contact_person_ur' => '',
            'phone_number' => '',
            'is_primary' => true,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function emptyAddressRow(): array
    {
        return [
            'address_en' => '',
            'address_ur' => '',
            'postal_code' => '',
            'is_primary' => true,
        ];
    }

    /**
     * @return list<array{key: string, label: string}>
     */
    public function designationOptions(): array
    {
        $locale = app()->getLocale();
        $options = [];

        foreach (config('company_contact_designations', []) as $key => $labels) {
            if (! is_array($labels)) {
                continue;
            }

            $options[] = [
                'key' => (string) $key,
                'label' => $locale === 'ur'
                    ? ($labels['ur'] ?? $labels['en'] ?? $key)
                    : ($labels['en'] ?? $key),
            ];
        }

        return $options;
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    private function migratePhoneRow(array $row): array
    {
        return [
            'designation' => $this->normalizeDesignation((string) ($row['designation'] ?? 'general')),
            'contact_person_en' => trim((string) ($row['contact_person_en'] ?? '')),
            'contact_person_ur' => trim((string) ($row['contact_person_ur'] ?? '')),
            'phone_number' => trim((string) ($row['phone_number'] ?? '')),
            'is_primary' => ! empty($row['is_primary']),
        ];
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    private function migrateAddressRow(array $row): array
    {
        $addressEn = trim((string) ($row['address_en'] ?? ''));
        $addressUr = trim((string) ($row['address_ur'] ?? ''));

        if ($addressEn === '' && $addressUr === '') {
            $addressEn = $this->composeLegacyAddress($row, 'en');
            $addressUr = $this->composeLegacyAddress($row, 'ur');
        }

        return [
            'address_en' => $addressEn,
            'address_ur' => $addressUr,
            'postal_code' => trim((string) ($row['postal_code'] ?? '')),
            'is_primary' => ! empty($row['is_primary']),
        ];
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function composeLegacyAddress(array $row, string $locale): string
    {
        $suffix = $locale === 'ur' ? '_ur' : '_en';
        $parts = array_filter([
            $row['line1' . $suffix] ?? $row['line1_en'] ?? null,
            $row['line2' . $suffix] ?? $row['line2_en'] ?? null,
            $row['city' . $suffix] ?? $row['city_en'] ?? null,
            $row['province' . $suffix] ?? $row['province_en'] ?? null,
            $row['country'] ?? null,
        ], static fn ($part): bool => filled($part));

        return implode(', ', $parts);
    }

    private function normalizeDesignation(string $designation): string
    {
        $allowed = array_keys(config('company_contact_designations', []));

        return in_array($designation, $allowed, true) ? $designation : 'general';
    }
}
