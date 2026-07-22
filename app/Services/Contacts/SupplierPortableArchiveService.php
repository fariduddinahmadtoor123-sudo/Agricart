<?php

namespace App\Services\Contacts;

use App\Models\Category;
use App\Models\Supplier;
use App\Services\PortableArchive\PortableArchiveCodec;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Portable encrypted supplier archive (.ags) — uses shared PortableArchiveCodec.
 * Passwords are never exported.
 */
class SupplierPortableArchiveService
{
    public const FORMAT = 'agricart.suppliers.portable';

    public const VERSION = 1;

    public function __construct(
        private readonly PortableArchiveCodec $codec,
    ) {}

    /**
     * @return array{path: string, filename: string, count: int}
     */
    public function exportAll(): array
    {
        $suppliers = Supplier::query()
            ->with('categories:id,code,slug,name_en,name_ur')
            ->orderBy('id')
            ->get();

        $workDir = storage_path('app/tmp/supplier-export-'.Str::lower(Str::random(8)));
        File::ensureDirectoryExists($workDir);
        File::ensureDirectoryExists($workDir.'/media');

        $payload = [];

        foreach ($suppliers as $supplier) {
            $code = (string) $supplier->supplier_code;
            $media = [
                'profile_photo' => $this->codec->copyMediaIntoArchive($workDir, $code, 'profile_photo', $supplier->profile_photo_path),
                'card_front' => $this->codec->copyMediaIntoArchive($workDir, $code, 'card_front', $supplier->card_front_path),
                'card_back' => $this->codec->copyMediaIntoArchive($workDir, $code, 'card_back', $supplier->card_back_path),
            ];

            $payload[] = [
                'supplier_code' => $code,
                'supplier_type' => $supplier->supplier_type,
                'status' => $supplier->status,
                'business_name_en' => $supplier->business_name_en,
                'business_name_ur' => $supplier->business_name_ur,
                'country_code' => $supplier->country_code,
                'country_ur' => $supplier->country_ur,
                'city_key' => $supplier->city_key,
                'city_en' => $supplier->city_en,
                'city_ur' => $supplier->city_ur,
                'address_en' => $supplier->address_en,
                'address_ur' => $supplier->address_ur,
                'credit_limit' => $supplier->credit_limit,
                'opening_balance' => $supplier->opening_balance,
                'contacts' => $supplier->contacts ?? [],
                'bank_accounts' => $supplier->bank_accounts ?? [],
                'portal_enabled' => (bool) $supplier->portal_enabled,
                'email' => $supplier->email,
                'ai_status' => $supplier->ai_status?->value ?? 'none',
                'categories' => $supplier->categories->map(static fn (Category $category): array => [
                    'code' => $category->code,
                    'slug' => $category->slug,
                    'name_en' => $category->name_en,
                    'name_ur' => $category->name_ur,
                ])->values()->all(),
                'media' => $media,
            ];
        }

        $manifest = [
            'format' => self::FORMAT,
            'version' => self::VERSION,
            'entity' => 'suppliers',
            'app' => (string) config('app.name', 'Agricart'),
            'exported_at' => now()->toIso8601String(),
            'supplier_count' => count($payload),
        ];

        try {
            $sealed = $this->codec->sealDirectory(
                $workDir,
                'suppliers.json',
                $manifest,
                $payload,
                $this->codec->datedFilename(['suppliers']),
            );
        } finally {
            File::deleteDirectory($workDir);
        }

        return [
            'path' => $sealed['path'],
            'filename' => $sealed['filename'],
            'count' => count($payload),
        ];
    }

    /**
     * @return array{created: int, updated: int, skipped: int, media: int}
     */
    public function importFromPath(string $archivePath): array
    {
        $pack = $this->codec->unsealToExtracted($archivePath);

        try {
            $parsed = $this->codec->readManifestAndRecords($pack['extracted'], self::FORMAT, 'suppliers.json');
            $rows = $parsed['records'];
            $created = 0;
            $updated = 0;
            $skipped = 0;
            $media = 0;
            $service = app(SupplierService::class);

            DB::transaction(function () use ($rows, $pack, $service, &$created, &$updated, &$skipped, &$media): void {
                foreach ($rows as $row) {
                    if (! is_array($row) || ! filled($row['supplier_code'] ?? null)) {
                        $skipped++;

                        continue;
                    }

                    $code = (string) $row['supplier_code'];
                    $existing = Supplier::query()->where('supplier_code', $code)->first();
                    $categoryIds = $this->resolveCategoryIds(is_array($row['categories'] ?? null) ? $row['categories'] : []);
                    $mediaMap = is_array($row['media'] ?? null) ? $row['media'] : [];
                    $extractedRoot = $pack['extracted'];

                    $paths = [
                        'profile_photo_path' => $existing?->profile_photo_path,
                        'card_front_path' => $existing?->card_front_path,
                        'card_back_path' => $existing?->card_back_path,
                    ];

                    foreach ([
                        'profile_photo' => ['field' => 'profile_photo_path', 'dir' => 'suppliers/profile-photos'],
                        'card_front' => ['field' => 'card_front_path', 'dir' => 'suppliers/cards'],
                        'card_back' => ['field' => 'card_back_path', 'dir' => 'suppliers/cards'],
                    ] as $role => $meta) {
                        $restored = $this->codec->restoreMediaFile($extractedRoot, $mediaMap[$role] ?? null, $meta['dir']);
                        if ($restored !== null) {
                            $paths[$meta['field']] = $restored;
                            $media++;
                        }
                    }

                    $email = filled($row['email'] ?? null) ? (string) $row['email'] : null;

                    if ($email !== null) {
                        $emailTaken = Supplier::query()
                            ->where('email', $email)
                            ->when($existing !== null, fn ($q) => $q->where('id', '!=', $existing->id))
                            ->exists();

                        if ($emailTaken) {
                            $email = null;
                        }
                    }

                    $data = [
                        'supplier_code' => $code,
                        'supplier_type' => $row['supplier_type'] ?? null,
                        'status' => in_array(($row['status'] ?? 'active'), ['active', 'inactive'], true)
                            ? $row['status']
                            : 'active',
                        'business_name_en' => $row['business_name_en'] ?? null,
                        'business_name_ur' => $row['business_name_ur'] ?? null,
                        'country_code' => filled($row['country_code'] ?? null) ? (string) $row['country_code'] : 'PK',
                        'country_ur' => $row['country_ur'] ?? null,
                        'city_key' => $row['city_key'] ?? null,
                        'city_en' => $row['city_en'] ?? null,
                        'city_ur' => $row['city_ur'] ?? null,
                        'address_en' => $row['address_en'] ?? null,
                        'address_ur' => $row['address_ur'] ?? null,
                        'credit_limit' => $row['credit_limit'] ?? null,
                        'opening_balance' => $row['opening_balance'] ?? 0,
                        'contacts' => is_array($row['contacts'] ?? null) ? $row['contacts'] : [],
                        'bank_accounts' => is_array($row['bank_accounts'] ?? null) ? $row['bank_accounts'] : [],
                        'portal_enabled' => false,
                        'email' => $email,
                        'ai_status' => $row['ai_status'] ?? 'none',
                        'profile_photo_path' => $paths['profile_photo_path'],
                        'card_front_path' => $paths['card_front_path'],
                        'card_back_path' => $paths['card_back_path'],
                        'category_ids' => $categoryIds,
                    ];

                    try {
                        if ($existing === null) {
                            $service->create($data);
                            $created++;
                        } else {
                            $service->update($existing, $data);
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
     * @param  list<array<string, mixed>>  $categories
     * @return list<int>
     */
    protected function resolveCategoryIds(array $categories): array
    {
        $ids = [];

        foreach ($categories as $category) {
            if (! is_array($category)) {
                continue;
            }

            $match = null;

            if (filled($category['code'] ?? null)) {
                $match = Category::query()->where('code', $category['code'])->first();
            }

            if ($match === null && filled($category['slug'] ?? null)) {
                $match = Category::query()->where('slug', $category['slug'])->first();
            }

            if ($match !== null) {
                $ids[] = (int) $match->id;
            }
        }

        return array_values(array_unique($ids));
    }
}
