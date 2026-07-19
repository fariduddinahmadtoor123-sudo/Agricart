<?php

namespace App\Services\PricingControls;

use App\Models\QuantityChannelTier;
use App\Models\QuantityControl;
use App\Services\Identifiers\DocumentCounterService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class QuantityControlService
{
    /** @var list<string> */
    public const STATUSES = ['active', 'inactive'];

    public function __construct(
        private readonly QuantityPricingEngine $engine,
    ) {}

    public function ensureDefaultProfile(): QuantityControl
    {
        $this->ensureSqliteMinQuantityIndexRemoved();
        $this->normalizeTierSortOrders();

        $profile = QuantityControl::query()
            ->with('tiers')
            ->orderBy('id')
            ->first();

        if ($profile instanceof QuantityControl) {
            if ($profile->tiers->isEmpty()) {
                $this->seedDefaultTiers($profile);
            }

            return $profile->fresh(['tiers']);
        }

        return DB::transaction(function (): QuantityControl {
            $profile = QuantityControl::query()->create([
                'control_number' => $this->nextControlNumber(),
                'name_en' => 'Default quantity channel',
                'name_ur' => 'ڈیفالٹ مقدار چینل',
                'status' => 'active',
                'notes' => null,
                'settings' => ['base' => 'min_quantity'],
                'created_by' => auth()->id(),
            ]);

            $this->seedDefaultTiers($profile);

            return $profile->fresh(['tiers']);
        });
    }

    public function create(array $data): QuantityControl
    {
        $validated = $this->validateProfile($data);

        $profile = DB::transaction(function () use ($validated): QuantityControl {
            if (! filled($validated['control_number'] ?? null)) {
                $validated['control_number'] = $this->nextControlNumber();
            }

            $validated['created_by'] = $validated['created_by'] ?? auth()->id();

            return QuantityControl::query()->create($validated);
        });

        $this->engine->clearCache();

        return $profile;
    }

    public function update(QuantityControl $record, array $data): QuantityControl
    {
        $validated = $this->validateProfile($data, $record->id);
        $record->update($validated);
        $this->engine->clearCache();

        return $record->fresh(['creator', 'tiers']);
    }

    public function delete(QuantityControl $record): void
    {
        if (QuantityControl::query()->count() <= 1) {
            throw ValidationException::withMessages([
                'profile' => __('quantity_controls.messages.cannot_delete_last_profile'),
            ]);
        }

        $record->delete();
        $this->engine->clearCache();
    }

    public function createTier(QuantityControl $profile, array $data): QuantityChannelTier
    {
        $validated = $this->validateTier($data, $profile);

        $tier = $profile->tiers()->create($validated);
        $this->engine->clearCache();

        return $tier;
    }

    public function updateTier(QuantityChannelTier $tier, array $data): QuantityChannelTier
    {
        $validated = $this->validateTier($data, $tier->quantityControl, $tier->id);
        $tier->update($validated);
        $this->engine->clearCache();

        return $tier->fresh();
    }

    public function deleteTier(QuantityChannelTier $tier): void
    {
        $tier->delete();
        $this->engine->clearCache();
    }

    public function nextControlNumber(): string
    {
        return app(DocumentCounterService::class)->next(
            'quantity_controls.control_number',
            'QTC-',
            5,
            fn (): int => DocumentCounterService::maximumSuffix('quantity_controls', 'control_number', 'QTC-'),
        );
    }

    private function ensureSqliteMinQuantityIndexRemoved(): void
    {
        if (DB::connection()->getDriverName() !== 'sqlite') {
            return;
        }

        if (Cache::get('agricart.qty_channel_min_index_dropped') === true) {
            return;
        }

        if (! Schema::hasTable('quantity_channel_tiers')) {
            return;
        }

        try {
            $indexes = DB::select("SELECT name FROM sqlite_master WHERE type='index' AND tbl_name='quantity_channel_tiers'");

            foreach ($indexes as $index) {
                $name = $index->name;

                if (str_contains($name, 'min_quantity')) {
                    DB::statement('DROP INDEX IF EXISTS ' . $name);
                }
            }

            Cache::forever('agricart.qty_channel_min_index_dropped', true);
        } catch (\Throwable) {
            // Will retry on the next request if the database is busy.
        }
    }

    private function normalizeTierSortOrders(): void
    {
        if (! Schema::hasTable('quantity_channel_tiers')) {
            return;
        }

        foreach (QuantityPricingEngine::TIER_SORT_ORDER as $tierKey => $sortOrder) {
            QuantityChannelTier::query()
                ->where('tier_key', $tierKey)
                ->where('sort_order', '!=', $sortOrder)
                ->update(['sort_order' => $sortOrder]);
        }

        $nameUpdates = [
            'retail' => ['name_en' => 'Retail quantity', 'name_ur' => 'ریٹیل کوانٹٹی'],
            'wholesale' => ['name_en' => 'Wholesale quantity', 'name_ur' => 'ہول سیل کوانٹٹی'],
            'super_wholesale' => ['name_en' => 'Bulk quantity', 'name_ur' => 'بلک کوانٹٹی'],
            'distributor' => ['name_en' => 'Distributor quantity', 'name_ur' => 'ڈسٹریبیوٹر کوانٹٹی'],
        ];

        foreach ($nameUpdates as $tierKey => $names) {
            QuantityChannelTier::query()
                ->where('tier_key', $tierKey)
                ->update($names);
        }
    }

    private function seedDefaultTiers(QuantityControl $profile): void
    {
        $defaults = [
            ['tier_key' => 'retail', 'name_en' => 'Retail quantity', 'name_ur' => 'ریٹیل کوانٹٹی', 'min_quantity' => 1, 'price_field' => 'retail_price', 'sort_order' => 1],
            ['tier_key' => 'wholesale', 'name_en' => 'Wholesale quantity', 'name_ur' => 'ہول سیل کوانٹٹی', 'min_quantity' => 5, 'price_field' => 'wholesale_price', 'sort_order' => 2],
            ['tier_key' => 'super_wholesale', 'name_en' => 'Bulk quantity', 'name_ur' => 'بلک کوانٹٹی', 'min_quantity' => 20, 'price_field' => 'super_wholesale_price', 'sort_order' => 3],
            ['tier_key' => 'distributor', 'name_en' => 'Distributor quantity', 'name_ur' => 'ڈسٹریبیوٹر کوانٹٹی', 'min_quantity' => 500, 'price_field' => 'distributor_price', 'sort_order' => 4],
        ];

        foreach ($defaults as $tier) {
            $profile->tiers()->create([
                ...$tier,
                'status' => 'active',
            ]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function validateProfile(array $data, ?int $ignoreId = null): array
    {
        return validator($data, [
            'control_number' => [
                'nullable',
                'string',
                'max:32',
                Rule::unique('quantity_controls', 'control_number')->ignore($ignoreId),
            ],
            'name_en' => ['required', 'string', 'max:255'],
            'name_ur' => ['nullable', 'string', 'max:255'],
            'status' => ['required', Rule::in(self::STATUSES)],
            'notes' => ['nullable', 'string', 'max:5000'],
            'settings' => ['nullable', 'array'],
            'created_by' => ['nullable', 'integer', 'exists:users,id'],
        ])->validate();
    }

    /**
     * @return array<string, mixed>
     */
    private function validateTier(array $data, QuantityControl $profile, ?int $ignoreTierId = null): array
    {
        $validated = validator($data, [
            'tier_key' => [
                'required',
                'string',
                Rule::in(array_keys(QuantityPricingEngine::PRICE_TIER_TYPES)),
                Rule::unique('quantity_channel_tiers', 'tier_key')
                    ->where('quantity_control_id', $profile->id)
                    ->ignore($ignoreTierId),
            ],
            'name_en' => ['required', 'string', 'max:120'],
            'name_ur' => ['nullable', 'string', 'max:120'],
            'min_quantity' => ['required', 'integer', 'min:1', 'max:9999999'],
            'status' => ['required', Rule::in(self::STATUSES)],
        ])->validate();

        $definition = QuantityPricingEngine::PRICE_TIER_TYPES[$validated['tier_key']];
        $validated['price_field'] = $definition['price_field'];
        $validated['sort_order'] = QuantityPricingEngine::TIER_SORT_ORDER[$validated['tier_key']] ?? 99;

        return $validated;
    }
}
