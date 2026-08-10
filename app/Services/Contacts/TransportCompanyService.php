<?php

namespace App\Services\Contacts;

use App\Models\TransportCompany;
use App\Services\Identifiers\DocumentCounterService;
use App\Services\Media\OptimizedImageStorage;
use App\Support\PakistaniBanks;
use App\Support\PakistaniCities;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class TransportCompanyService
{
    public function nextTransportCode(): string
    {
        return app(DocumentCounterService::class)->next(
            'transport_companies.transport_code',
            'TRP-',
            5,
            fn (): int => DocumentCounterService::maximumSuffix('transport_companies', 'transport_code', 'TRP-'),
        );
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): TransportCompany
    {
        $data = $this->validate($data);

        if (blank($data['transport_code'] ?? null)) {
            $data['transport_code'] = $this->nextTransportCode();
        }

        return TransportCompany::query()->create($data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(TransportCompany $company, array $data): TransportCompany
    {
        $data = $this->validate($data, $company->id);

        if (blank($data['transport_code'] ?? null)) {
            unset($data['transport_code']);
        }

        $company->update($data);

        return $company->refresh();
    }

    public function delete(TransportCompany $company): void
    {
        $company->delete();
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function validate(array $data, ?int $exceptId = null): array
    {
        $data['contacts'] = $this->normalizeContacts($data['contacts'] ?? []);
        $data['bank_accounts'] = $this->normalizeBankRows($data['bank_accounts'] ?? []);
        $data['routes'] = $this->normalizeRoutes($data['routes'] ?? []);
        $data['branches'] = $this->normalizeBranches($data['branches'] ?? []);
        $data['destination_branches'] = $this->normalizeBranches($data['destination_branches'] ?? []);

        // Backfill English business name from Urdu when only Urdu is provided.
        if (blank($data['business_name_en'] ?? null) && filled($data['business_name_ur'] ?? null)) {
            $data['business_name_en'] = $data['business_name_ur'];
        }

        if (filled($data['city_key'] ?? null)) {
            $data['city_en'] = PakistaniCities::english((string) $data['city_key']);
            $data['city_ur'] = PakistaniCities::urdu((string) $data['city_key']);
        }

        if (filled($data['destination_city_key'] ?? null)) {
            $data['destination_city_en'] = PakistaniCities::english((string) $data['destination_city_key']);
            $data['destination_city_ur'] = PakistaniCities::urdu((string) $data['destination_city_key']);
        }

        if (blank($data['destination_country_code'] ?? null)) {
            $data['destination_country_code'] = 'PK';
        }

        $rules = [
            'transport_code' => ['nullable', 'string', 'max:32'],
            'transport_type' => ['nullable', 'string', 'max:64'],
            'status' => ['required', 'string', 'in:active,inactive'],
            'business_name_en' => ['required_without:business_name_ur', 'nullable', 'string', 'max:255'],
            'business_name_ur' => ['required_without:business_name_en', 'nullable', 'string', 'max:255'],
            'country_code' => ['required', 'string', 'max:8'],
            'country_ur' => ['nullable', 'string', 'max:255'],
            'city_key' => ['nullable', 'string', 'max:64'],
            'city_en' => ['nullable', 'string', 'max:255'],
            'city_ur' => ['nullable', 'string', 'max:255'],
            'address_en' => ['nullable', 'string', 'max:2000'],
            'address_ur' => ['nullable', 'string', 'max:2000'],
            'destination_business_name_en' => ['nullable', 'string', 'max:255'],
            'destination_business_name_ur' => ['nullable', 'string', 'max:255'],
            'destination_country_code' => ['nullable', 'string', 'max:8'],
            'destination_country_ur' => ['nullable', 'string', 'max:255'],
            'destination_city_key' => ['nullable', 'string', 'max:64'],
            'destination_city_en' => ['nullable', 'string', 'max:255'],
            'destination_city_ur' => ['nullable', 'string', 'max:255'],
            'destination_address_en' => ['nullable', 'string', 'max:2000'],
            'destination_address_ur' => ['nullable', 'string', 'max:2000'],
            'routes' => ['nullable', 'array'],
            'routes.*.from_key' => ['nullable', 'string', 'max:64'],
            'routes.*.to_key' => ['nullable', 'string', 'max:64'],
            'branches' => ['nullable', 'array'],
            'branches.*.address_en' => ['nullable', 'string', 'max:2000'],
            'branches.*.address_ur' => ['nullable', 'string', 'max:2000'],
            'branches.*.contacts' => ['nullable', 'array'],
            'destination_branches' => ['nullable', 'array'],
            'destination_branches.*.address_en' => ['nullable', 'string', 'max:2000'],
            'destination_branches.*.address_ur' => ['nullable', 'string', 'max:2000'],
            'destination_branches.*.contacts' => ['nullable', 'array'],
            'contacts' => ['nullable', 'array'],
            'contacts.*.contact_name_en' => ['nullable', 'string', 'max:255'],
            'contacts.*.contact_name_ur' => ['nullable', 'string', 'max:255'],
            'contacts.*.mobile_number' => ['nullable', 'string', 'max:30'],
            'bank_accounts' => ['nullable', 'array'],
            'bank_accounts.*.bank_key' => ['nullable', 'string', 'max:64'],
            'bank_accounts.*.account_title' => ['nullable', 'string', 'max:255'],
            'bank_accounts.*.account_number' => ['nullable', 'string', 'max:64'],
            'credit_limit' => ['nullable', 'numeric'],
            'opening_balance' => ['nullable', 'numeric'],
            'profile_photo_path' => ['nullable', 'string', 'max:500'],
            'card_front_path' => ['nullable', 'string', 'max:500'],
            'card_back_path' => ['nullable', 'string', 'max:500'],
        ];

        $validated = Validator::make($data, $rules)->validate();

        $validated['contacts'] = $data['contacts'];
        $validated['bank_accounts'] = $data['bank_accounts'];
        $validated['routes'] = $data['routes'];
        $validated['branches'] = $data['branches'];
        $validated['destination_branches'] = $data['destination_branches'];
        $validated['opening_balance'] = $data['opening_balance'] ?? ($validated['opening_balance'] ?? 0);
        $validated['credit_limit'] = ($data['credit_limit'] ?? '') !== '' ? $data['credit_limit'] : null;
        $validated['transport_type'] = $data['transport_type'] ?? null;
        $validated['country_ur'] = $data['country_ur'] ?? null;
        $validated['city_key'] = $data['city_key'] ?? null;
        $validated['city_en'] = $data['city_en'] ?? null;
        $validated['city_ur'] = $data['city_ur'] ?? null;
        $validated['destination_business_name_en'] = $data['destination_business_name_en'] ?? null;
        $validated['destination_business_name_ur'] = $data['destination_business_name_ur'] ?? null;
        $validated['destination_country_code'] = $data['destination_country_code'] ?? 'PK';
        $validated['destination_country_ur'] = $data['destination_country_ur'] ?? null;
        $validated['destination_city_key'] = $data['destination_city_key'] ?? null;
        $validated['destination_city_en'] = $data['destination_city_en'] ?? null;
        $validated['destination_city_ur'] = $data['destination_city_ur'] ?? null;
        $validated['destination_address_en'] = $data['destination_address_en'] ?? null;
        $validated['destination_address_ur'] = $data['destination_address_ur'] ?? null;

        return $validated;
    }

    /**
     * @param  array<int, mixed>  $rows
     * @return list<array{contact_name_en: string, contact_name_ur: string, mobile_number: string}>
     */
    public function normalizeContacts(array $rows): array
    {
        return array_values(array_filter(
            array_map(static fn (array $row): array => [
                'contact_name_en' => trim((string) ($row['contact_name_en'] ?? '')),
                'contact_name_ur' => trim((string) ($row['contact_name_ur'] ?? '')),
                'mobile_number' => trim((string) ($row['mobile_number'] ?? '')),
            ], array_filter($rows, 'is_array')),
            static fn (array $row): bool => filled($row['mobile_number'])
                || filled($row['contact_name_en'])
                || filled($row['contact_name_ur']),
        ));
    }

    /**
     * @param  array<int, mixed>  $rows
     * @return list<array<string, string>>
     */
    public function normalizeBankRows(array $rows): array
    {
        $normalized = [];

        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }

            $bankKey = trim((string) ($row['bank_key'] ?? ''));

            if ($bankKey === '' && ! filled($row['account_title'] ?? null) && ! filled($row['account_number'] ?? null)) {
                continue;
            }

            $normalized[] = [
                'bank_key' => $bankKey,
                'bank_name_en' => $bankKey !== '' ? PakistaniBanks::english($bankKey) : trim((string) ($row['bank_name_en'] ?? '')),
                'bank_name_ur' => $bankKey !== '' ? PakistaniBanks::urdu($bankKey) : trim((string) ($row['bank_name_ur'] ?? '')),
                'account_title' => trim((string) ($row['account_title'] ?? '')),
                'account_title_ur' => trim((string) ($row['account_title_ur'] ?? '')),
                'account_number' => trim((string) ($row['account_number'] ?? '')),
            ];
        }

        return $normalized;
    }

    /**
     * @param  array<int, mixed>  $rows
     * @return list<array<string, string>>
     */
    public function normalizeRoutes(array $rows): array
    {
        $normalized = [];

        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }

            $fromKey = trim((string) ($row['from_key'] ?? ''));
            $toKey = trim((string) ($row['to_key'] ?? ''));
            $fromManual = trim((string) ($row['from_en'] ?? ''));
            $toManual = trim((string) ($row['to_en'] ?? ''));

            if ($fromKey === '' && $toKey === '' && $fromManual === '' && $toManual === '') {
                continue;
            }

            $normalized[] = [
                'from_key' => $fromKey,
                'from_en' => $fromKey !== '' ? PakistaniCities::english($fromKey) : $fromManual,
                'from_ur' => $fromKey !== '' ? PakistaniCities::urdu($fromKey) : trim((string) ($row['from_ur'] ?? '')),
                'to_key' => $toKey,
                'to_en' => $toKey !== '' ? PakistaniCities::english($toKey) : $toManual,
                'to_ur' => $toKey !== '' ? PakistaniCities::urdu($toKey) : trim((string) ($row['to_ur'] ?? '')),
            ];
        }

        return $normalized;
    }

    /**
     * @param  array<int, mixed>  $rows
     * @return list<array{address_en: string, address_ur: string, contacts: list<array{contact_name_en: string, contact_name_ur: string, mobile_number: string}>}>
     */
    public function normalizeBranches(array $rows): array
    {
        $normalized = [];

        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }

            $addressEn = trim((string) ($row['address_en'] ?? ''));
            $addressUr = trim((string) ($row['address_ur'] ?? ''));
            $contacts = $this->normalizeContacts(is_array($row['contacts'] ?? null) ? $row['contacts'] : []);

            if ($addressEn === '' && $addressUr === '' && $contacts === []) {
                continue;
            }

            $normalized[] = [
                'address_en' => $addressEn,
                'address_ur' => $addressUr,
                'contacts' => $contacts,
            ];
        }

        return $normalized;
    }

    /**
     * @return array<string, mixed>
     */
    public function toFormState(TransportCompany $company): array
    {
        $contacts = is_array($company->contacts) ? $company->contacts : [];
        $banks = is_array($company->bank_accounts) ? $company->bank_accounts : [];
        $routes = is_array($company->routes) ? $company->routes : [];
        $branches = is_array($company->branches) ? $company->branches : [];
        $destinationBranches = is_array($company->destination_branches) ? $company->destination_branches : [];

        $emptyContact = ['contact_name_en' => '', 'contact_name_ur' => '', 'mobile_number' => ''];
        $emptyBranch = ['address_en' => '', 'address_ur' => '', 'contacts' => [$emptyContact]];
        $destinationCountry = (string) ($company->destination_country_code ?: 'PK');

        return [
            'transport_code' => (string) $company->transport_code,
            'transport_type' => (string) ($company->transport_type ?? ''),
            'status' => (string) $company->status,
            'business_name_en' => (string) ($company->business_name_en ?? ''),
            'business_name_ur' => (string) ($company->business_name_ur ?? ''),
            'country_code' => (string) ($company->country_code ?: 'PK'),
            'country_ur' => (string) ($company->country_ur ?? ''),
            'city_key' => (string) ($company->city_key ?? ''),
            'city_manual' => (string) (($company->country_code ?? 'PK') !== 'PK' ? ($company->city_en ?? '') : ''),
            'city_en' => (string) ($company->city_en ?? ''),
            'city_ur' => (string) ($company->city_ur ?? ''),
            'address_en' => (string) ($company->address_en ?? ''),
            'address_ur' => (string) ($company->address_ur ?? ''),
            'destination_business_name_en' => (string) ($company->destination_business_name_en ?? ''),
            'destination_business_name_ur' => (string) ($company->destination_business_name_ur ?? ''),
            'destination_country_code' => $destinationCountry,
            'destination_country_ur' => (string) ($company->destination_country_ur ?? ''),
            'destination_city_key' => (string) ($company->destination_city_key ?? ''),
            'destination_city_manual' => (string) ($destinationCountry !== 'PK' ? ($company->destination_city_en ?? '') : ''),
            'destination_city_en' => (string) ($company->destination_city_en ?? ''),
            'destination_city_ur' => (string) ($company->destination_city_ur ?? ''),
            'destination_address_en' => (string) ($company->destination_address_en ?? ''),
            'destination_address_ur' => (string) ($company->destination_address_ur ?? ''),
            'credit_limit' => $company->credit_limit !== null ? (string) $company->credit_limit : '',
            'opening_balance' => $company->opening_balance !== null ? (string) $company->opening_balance : '',
            'routeRows' => $routes !== []
                ? array_values(array_map(static fn (array $row): array => [
                    'from_key' => (string) ($row['from_key'] ?? ''),
                    'from_en' => (string) ($row['from_en'] ?? ''),
                    'from_ur' => (string) ($row['from_ur'] ?? ''),
                    'to_key' => (string) ($row['to_key'] ?? ''),
                    'to_en' => (string) ($row['to_en'] ?? ''),
                    'to_ur' => (string) ($row['to_ur'] ?? ''),
                ], array_filter($routes, 'is_array')))
                : [['from_key' => '', 'from_en' => '', 'from_ur' => '', 'to_key' => '', 'to_en' => '', 'to_ur' => '']],
            'branchRows' => $this->mapBranchRowsForForm($branches, $emptyContact, $emptyBranch),
            'destinationBranchRows' => $this->mapBranchRowsForForm($destinationBranches, $emptyContact, $emptyBranch),
            'contactRows' => $contacts !== []
                ? array_values(array_map(static fn (array $row): array => [
                    'contact_name_en' => (string) ($row['contact_name_en'] ?? ''),
                    'contact_name_ur' => (string) ($row['contact_name_ur'] ?? ''),
                    'mobile_number' => (string) ($row['mobile_number'] ?? ''),
                ], array_filter($contacts, 'is_array')))
                : [$emptyContact],
            'bankRows' => $banks !== []
                ? array_values(array_map(static fn (array $row): array => [
                    'bank_key' => (string) ($row['bank_key'] ?? ''),
                    'bank_name_en' => (string) ($row['bank_name_en'] ?? ''),
                    'bank_name_ur' => (string) ($row['bank_name_ur'] ?? ''),
                    'account_title' => (string) ($row['account_title'] ?? ''),
                    'account_title_ur' => (string) ($row['account_title_ur'] ?? ''),
                    'account_number' => (string) ($row['account_number'] ?? ''),
                ], array_filter($banks, 'is_array')))
                : [['bank_key' => '', 'bank_name_en' => '', 'bank_name_ur' => '', 'account_title' => '', 'account_title_ur' => '', 'account_number' => '']],
            'profile_photo_path' => $company->profile_photo_path,
            'card_front_path' => $company->card_front_path,
            'card_back_path' => $company->card_back_path,
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $branches
     * @param  array{contact_name_en: string, contact_name_ur: string, mobile_number: string}  $emptyContact
     * @param  array{address_en: string, address_ur: string, contacts: list<array{contact_name_en: string, contact_name_ur: string, mobile_number: string}>}  $emptyBranch
     * @return list<array{address_en: string, address_ur: string, contacts: list<array{contact_name_en: string, contact_name_ur: string, mobile_number: string}>}>
     */
    private function mapBranchRowsForForm(array $branches, array $emptyContact, array $emptyBranch): array
    {
        if ($branches === []) {
            return [$emptyBranch];
        }

        return array_values(array_map(static function (array $row) use ($emptyContact): array {
            $branchContacts = is_array($row['contacts'] ?? null) ? $row['contacts'] : [];
            $mappedContacts = array_values(array_map(static fn (array $contact): array => [
                'contact_name_en' => (string) ($contact['contact_name_en'] ?? ''),
                'contact_name_ur' => (string) ($contact['contact_name_ur'] ?? ''),
                'mobile_number' => (string) ($contact['mobile_number'] ?? ''),
            ], array_filter($branchContacts, 'is_array')));

            return [
                'address_en' => (string) ($row['address_en'] ?? ''),
                'address_ur' => (string) ($row['address_ur'] ?? ''),
                'contacts' => $mappedContacts !== [] ? $mappedContacts : [$emptyContact],
            ];
        }, array_filter($branches, 'is_array')));
    }

    public function storeUpload(TemporaryUploadedFile $upload, string $directory): string
    {
        return app(OptimizedImageStorage::class)->store($upload, $directory);
    }

    public function storageUrl(?string $path): ?string
    {
        return filled($path) ? Storage::disk(config('media.disk', 'public'))->url($path) : null;
    }
}
