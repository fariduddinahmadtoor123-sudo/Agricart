<?php

namespace App\Services\Contacts;

use App\Models\Customer;
use App\Services\PortableArchive\PortableArchiveCodec;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Portable encrypted customer archive (.ags).
 * Supports channel scope: all | online | offline. Passwords are never exported.
 */
class CustomerPortableArchiveService
{
    public const FORMAT = 'agricart.customers.portable';

    public const VERSION = 1;

    public const SCOPE_ALL = 'all';

    public const SCOPE_ONLINE = 'online';

    public const SCOPE_OFFLINE = 'offline';

    public function __construct(
        private readonly PortableArchiveCodec $codec,
        private readonly CustomerService $customers,
    ) {}

    /**
     * @param  self::SCOPE_*  $scope
     * @return array{path: string, filename: string, count: int, scope: string}
     */
    public function export(string $scope = self::SCOPE_ALL): array
    {
        $scope = $this->normalizeScope($scope);

        $query = Customer::query()->orderBy('id');

        if ($scope === self::SCOPE_ONLINE) {
            $query->where('customer_channel', Customer::CHANNEL_ONLINE);
        } elseif ($scope === self::SCOPE_OFFLINE) {
            $query->where('customer_channel', Customer::CHANNEL_OFFLINE);
        }

        $customers = $query->get();
        $workDir = storage_path('app/tmp/customer-export-'.Str::lower(Str::random(8)));
        File::ensureDirectoryExists($workDir);

        $payload = [];

        foreach ($customers as $customer) {
            $number = (string) ($customer->customer_number ?: 'id-'.$customer->id);
            $media = [
                'profile_photo' => $this->codec->copyMediaIntoArchive(
                    $workDir,
                    $number,
                    'profile_photo',
                    $customer->profile_photo_path,
                ),
            ];

            $payload[] = [
                'customer_number' => $customer->customer_number,
                'customer_channel' => $customer->customer_channel,
                'first_name' => $customer->first_name,
                'last_name' => $customer->last_name,
                'display_name' => $customer->display_name,
                'name_en' => $customer->name_en,
                'name_ur' => $customer->name_ur,
                'email' => $customer->email,
                'phone' => $customer->phone,
                'address_line1' => $customer->address_line1,
                'address_line2' => $customer->address_line2,
                'address_en' => $customer->address_en,
                'address_ur' => $customer->address_ur,
                'address_quick' => $customer->address_quick,
                'city' => $customer->city,
                'city_key' => $customer->city_key,
                'city_en' => $customer->city_en,
                'city_ur' => $customer->city_ur,
                'state' => $customer->state,
                'postal_code' => $customer->postal_code,
                'country' => $customer->country,
                'opening_balance' => $customer->opening_balance,
                'credit_limit' => $customer->credit_limit,
                'contacts' => $customer->contacts ?? [],
                'accepts_marketing' => (bool) $customer->accepts_marketing,
                'is_active' => (bool) $customer->is_active,
                'ai_status' => $customer->ai_status?->value ?? 'none',
                'media' => $media,
            ];
        }

        $filenameParts = ['customers'];
        if ($scope !== self::SCOPE_ALL) {
            $filenameParts[] = $scope;
        }

        $manifest = [
            'format' => self::FORMAT,
            'version' => self::VERSION,
            'entity' => 'customers',
            'scope' => $scope,
            'app' => (string) config('app.name', 'Agricart'),
            'exported_at' => now()->toIso8601String(),
            'customer_count' => count($payload),
        ];

        try {
            $sealed = $this->codec->sealDirectory(
                $workDir,
                'customers.json',
                $manifest,
                $payload,
                $this->codec->datedFilename($filenameParts),
            );
        } finally {
            File::deleteDirectory($workDir);
        }

        return [
            'path' => $sealed['path'],
            'filename' => $sealed['filename'],
            'count' => count($payload),
            'scope' => $scope,
        ];
    }

    /**
     * @return array{created: int, updated: int, skipped: int, media: int}
     */
    public function importFromPath(string $archivePath): array
    {
        $pack = $this->codec->unsealToExtracted($archivePath);

        try {
            $parsed = $this->codec->readManifestAndRecords($pack['extracted'], self::FORMAT, 'customers.json');
            $rows = $parsed['records'];
            $created = 0;
            $updated = 0;
            $skipped = 0;
            $media = 0;

            DB::transaction(function () use ($rows, $pack, &$created, &$updated, &$skipped, &$media): void {
                foreach ($rows as $row) {
                    if (! is_array($row)) {
                        $skipped++;

                        continue;
                    }

                    $number = filled($row['customer_number'] ?? null) ? (string) $row['customer_number'] : null;
                    $existing = $number !== null
                        ? Customer::query()->where('customer_number', $number)->first()
                        : null;

                    $channel = in_array(($row['customer_channel'] ?? ''), [Customer::CHANNEL_ONLINE, Customer::CHANNEL_OFFLINE], true)
                        ? (string) $row['customer_channel']
                        : Customer::CHANNEL_OFFLINE;

                    $photo = $this->codec->restoreMediaFile(
                        $pack['extracted'],
                        is_array($row['media'] ?? null) ? ($row['media']['profile_photo'] ?? null) : null,
                        'customers/photos',
                    );

                    if ($photo !== null) {
                        $media++;
                    }

                    $contacts = is_array($row['contacts'] ?? null) ? $row['contacts'] : [];
                    if ($contacts === [] && filled($row['phone'] ?? null)) {
                        $contacts = [['mobile_number' => (string) $row['phone']]];
                    }

                    try {
                        if ($channel === Customer::CHANNEL_OFFLINE) {
                            $this->importOffline($existing, $row, $contacts, $photo, $number);
                        } else {
                            $this->importOnline($existing, $row, $contacts, $photo, $number);
                        }

                        if ($existing === null) {
                            $created++;
                        } else {
                            $updated++;
                        }
                    } catch (ValidationException) {
                        $skipped++;
                    }
                }
            });

            return compact('created', 'updated', 'skipped', 'media');
        } finally {
            File::deleteDirectory($pack['work_dir']);
        }
    }

    /**
     * @param  array<string, mixed>  $row
     * @param  list<array<string, mixed>>  $contacts
     */
    protected function importOffline(?Customer $existing, array $row, array $contacts, ?string $photo, ?string $number): void
    {
        $display = (string) ($row['display_name'] ?? $row['name_en'] ?? $row['name_ur'] ?? trim(($row['first_name'] ?? '').' '.($row['last_name'] ?? '')));
        if ($display === '') {
            $display = $number ?: 'Customer';
        }

        $phones = $contacts !== [] ? $contacts : [];
        if ($phones === [] && filled($row['phone'] ?? null)) {
            $phones = [['mobile_number' => (string) $row['phone']]];
        }
        if ($phones === []) {
            throw ValidationException::withMessages([
                'phone' => [__('customers.validation.phone_required')],
            ]);
        }

        $data = [
            'customer_number' => $number,
            'display_name' => $display,
            'name_en' => $row['name_en'] ?? null,
            'name_ur' => $row['name_ur'] ?? null,
            'address_quick' => $row['address_quick'] ?? $row['address_line1'] ?? null,
            'address_en' => $row['address_en'] ?? null,
            'address_ur' => $row['address_ur'] ?? null,
            'city_key' => $row['city_key'] ?? null,
            'city_en' => $row['city_en'] ?? $row['city'] ?? null,
            'city_ur' => $row['city_ur'] ?? null,
            'opening_balance' => $row['opening_balance'] ?? 0,
            'credit_limit' => $row['credit_limit'] ?? null,
            'email' => $this->uniqueEmail(filled($row['email'] ?? null) ? (string) $row['email'] : null, $existing?->id),
            'accepts_marketing' => (bool) ($row['accepts_marketing'] ?? false),
            'is_active' => array_key_exists('is_active', $row) ? (bool) $row['is_active'] : true,
            'ai_status' => $row['ai_status'] ?? 'none',
            'phoneRows' => $phones,
        ];

        if ($existing === null) {
            $customer = $this->customers->createOffline($data);
            if ($photo !== null) {
                $customer->update(['profile_photo_path' => $photo]);
            }
        } else {
            $this->customers->update($existing, $data);
            if ($photo !== null) {
                $existing->update(['profile_photo_path' => $photo]);
            }
        }
    }

    /**
     * @param  array<string, mixed>  $row
     * @param  list<array<string, mixed>>  $contacts
     */
    protected function importOnline(?Customer $existing, array $row, array $contacts, ?string $photo, ?string $number): void
    {
        $email = $this->uniqueEmail(filled($row['email'] ?? null) ? (string) $row['email'] : null, $existing?->id);

        if ($email === null) {
            $email = 'online.'.Str::lower($number ?: Str::random(8)).'@restore.agricart.local';
        }

        $phone = (string) ($contacts[0]['mobile_number'] ?? $row['phone'] ?? '');
        $first = (string) ($row['first_name'] ?? '');
        $last = (string) ($row['last_name'] ?? '');
        $display = (string) ($row['display_name'] ?? $row['name_en'] ?? trim($first.' '.$last));

        if ($first === '') {
            $parts = preg_split('/\s+/', $display !== '' ? $display : 'Customer', 2) ?: ['Customer'];
            $first = $parts[0] ?? 'Customer';
            $last = $parts[1] ?? '-';
        }

        if ($last === '') {
            $last = '-';
        }

        $attributes = [
            'customer_number' => $number ?: $this->customers->nextCustomerNumber(),
            'customer_channel' => Customer::CHANNEL_ONLINE,
            'first_name' => $first,
            'last_name' => $last,
            'display_name' => $display !== '' ? $display : trim($first.' '.$last),
            'name_en' => $row['name_en'] ?? $display,
            'name_ur' => $row['name_ur'] ?? null,
            'email' => $email,
            'phone' => $phone !== '' ? $phone : null,
            'address_line1' => (string) ($row['address_line1'] ?? $row['address_quick'] ?? $row['address_en'] ?? '-'),
            'address_line2' => $row['address_line2'] ?? null,
            'address_en' => $row['address_en'] ?? null,
            'address_ur' => $row['address_ur'] ?? null,
            'address_quick' => $row['address_quick'] ?? null,
            'city' => (string) ($row['city'] ?? $row['city_en'] ?? '-'),
            'city_key' => $row['city_key'] ?? null,
            'city_en' => $row['city_en'] ?? $row['city'] ?? null,
            'city_ur' => $row['city_ur'] ?? null,
            'state' => (string) ($row['state'] ?? 'Punjab'),
            'postal_code' => $row['postal_code'] ?? null,
            'country' => (string) ($row['country'] ?? 'Pakistan'),
            'opening_balance' => $row['opening_balance'] ?? 0,
            'credit_limit' => $row['credit_limit'] ?? null,
            'contacts' => $contacts,
            'accepts_marketing' => (bool) ($row['accepts_marketing'] ?? false),
            'is_active' => array_key_exists('is_active', $row) ? (bool) $row['is_active'] : true,
            'ai_status' => $row['ai_status'] ?? 'none',
            'profile_photo_path' => $photo ?? $existing?->profile_photo_path,
        ];

        if ($existing === null) {
            $attributes['password'] = Hash::make(Str::random(40));
            $customer = Customer::query()->create($attributes);
            $this->customers->syncNormalizedPhones($customer, $contacts);
        } else {
            unset($attributes['password']);
            $existing->update($attributes);
            $this->customers->syncNormalizedPhones($existing->fresh(), $contacts);
        }
    }

    protected function uniqueEmail(?string $email, ?int $exceptId): ?string
    {
        if ($email === null || $email === '') {
            return null;
        }

        $taken = Customer::query()
            ->where('email', $email)
            ->when($exceptId !== null, fn ($q) => $q->where('id', '!=', $exceptId))
            ->exists();

        return $taken ? null : $email;
    }

    /**
     * @return self::SCOPE_*
     */
    protected function normalizeScope(string $scope): string
    {
        return match ($scope) {
            self::SCOPE_ONLINE, Customer::CHANNEL_ONLINE => self::SCOPE_ONLINE,
            self::SCOPE_OFFLINE, Customer::CHANNEL_OFFLINE => self::SCOPE_OFFLINE,
            default => self::SCOPE_ALL,
        };
    }
}
