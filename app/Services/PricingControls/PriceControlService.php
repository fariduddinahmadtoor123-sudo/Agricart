<?php

namespace App\Services\PricingControls;

use App\Models\PriceChannelTier;
use App\Models\PriceControl;
use App\Services\Identifiers\DocumentCounterService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PriceControlService
{
    /** @var list<string> */
    public const STATUSES = ['active', 'inactive'];

    public function __construct(
        private readonly ChannelPricingEngine $engine,
    ) {}

    public function ensureDefaultProfile(): PriceControl
    {
        $profile = PriceControl::query()
            ->with('tiers')
            ->orderBy('id')
            ->first();

        if ($profile instanceof PriceControl) {
            if ($profile->tiers->isEmpty()) {
                $this->seedDefaultTiers($profile);
            }

            $this->ensureRetailTier($profile);
            $this->normalizeTierCatalog($profile);

            return $profile->fresh(['tiers']);
        }

        return DB::transaction(function (): PriceControl {
            $profile = PriceControl::query()->create([
                'control_number' => $this->nextControlNumber(),
                'name_en' => 'Default price channel',
                'name_ur' => 'ڈیفالٹ قیمت چینل',
                'status' => 'active',
                'notes' => null,
                'settings' => ['base' => 'purchase_price'],
                'created_by' => auth()->id(),
            ]);

            $this->seedDefaultTiers($profile);

            return $profile->fresh(['tiers']);
        });
    }

    public function create(array $data): PriceControl
    {
        $validated = $this->validateProfile($data);

        return DB::transaction(function () use ($validated): PriceControl {
            if (! filled($validated['control_number'] ?? null)) {
                $validated['control_number'] = $this->nextControlNumber();
            }

            $validated['created_by'] = $validated['created_by'] ?? auth()->id();

            return PriceControl::query()->create($validated);
        });
    }

    public function update(PriceControl $record, array $data): PriceControl
    {
        $validated = $this->validateProfile($data, $record->id);
        $record->update($validated);

        return $record->fresh(['creator', 'tiers']);
    }

    public function delete(PriceControl $record): void
    {
        if (PriceControl::query()->count() <= 1) {
            throw ValidationException::withMessages([
                'profile' => __('price_controls.messages.cannot_delete_last_profile'),
            ]);
        }

        $record->delete();
    }

    public function createTier(PriceControl $profile, array $data): PriceChannelTier
    {
        $validated = $this->validateTier($data, $profile);

        return $profile->tiers()->create($validated);
    }

    public function updateTier(PriceChannelTier $tier, array $data): PriceChannelTier
    {
        if ($tier->isManual()) {
            return $this->updateRetailMarkup($tier, $data);
        }

        $validated = $this->validateTier($data, $tier->priceControl, $tier->id);
        $tier->update($validated);

        return $tier->fresh();
    }

    /**
     * Retail stays purchase-manual for sale_price, but markup % drives Purchasing → Price Update.
     */
    private function updateRetailMarkup(PriceChannelTier $tier, array $data): PriceChannelTier
    {
        $validated = validator($data, [
            'markup_percent' => ['required', 'numeric', 'min:0', 'max:500'],
            'status' => ['nullable', Rule::in(self::STATUSES)],
        ])->validate();

        $tier->update([
            'markup_percent' => $validated['markup_percent'],
            'status' => $validated['status'] ?? $tier->status,
            'product_field' => 'retail_price',
        ]);

        return $tier->fresh();
    }

    public function deleteTier(PriceChannelTier $tier): void
    {
        if ($tier->isManual()) {
            throw ValidationException::withMessages([
                'tier' => __('price_controls.messages.cannot_delete_retail'),
            ]);
        }

        $tier->delete();
    }

    public function nextControlNumber(): string
    {
        return app(DocumentCounterService::class)->next(
            'price_controls.control_number', 'PRC-', 5,
            fn (): int => DocumentCounterService::maximumSuffix('price_controls', 'control_number', 'PRC-'),
        );
    }

    private function seedDefaultTiers(PriceControl $profile): void
    {
        $defaults = [
            ['tier_key' => 'retail', 'name_en' => 'Retail price', 'name_ur' => 'ریٹیل قیمت', 'markup_percent' => 0, 'product_field' => 'retail_price', 'sort_order' => 1],
            ['tier_key' => 'wholesale', 'name_en' => 'Wholesale price', 'name_ur' => 'ہول سیل قیمت', 'markup_percent' => 10, 'product_field' => 'wholesale_price', 'sort_order' => 2],
            ['tier_key' => 'super_wholesale', 'name_en' => 'Bulk price', 'name_ur' => 'بلک قیمت', 'markup_percent' => 5, 'product_field' => 'super_wholesale_price', 'sort_order' => 3],
            ['tier_key' => 'distributor', 'name_en' => 'Distributor price', 'name_ur' => 'ڈسٹریبیوٹر قیمت', 'markup_percent' => 2, 'product_field' => 'distributor_price', 'sort_order' => 4],
        ];

        foreach ($defaults as $tier) {
            $profile->tiers()->create([
                ...$tier,
                'status' => 'active',
            ]);
        }
    }

    private function ensureRetailTier(PriceControl $profile): void
    {
        if ($profile->tiers()->where('tier_key', 'retail')->exists()) {
            return;
        }

        $definition = ChannelPricingEngine::PRICE_TIER_TYPES['retail'];

        $profile->tiers()->create([
            'tier_key' => 'retail',
            'name_en' => $definition['name_en'],
            'name_ur' => $definition['name_ur'],
            'markup_percent' => 0,
            'product_field' => $definition['price_field'],
            'sort_order' => ChannelPricingEngine::TIER_SORT_ORDER['retail'],
            'status' => 'active',
        ]);
    }

    private function normalizeTierCatalog(PriceControl $profile): void
    {
        if (! Schema::hasTable('price_channel_tiers')) {
            return;
        }

        foreach (ChannelPricingEngine::TIER_SORT_ORDER as $tierKey => $sortOrder) {
            $definition = ChannelPricingEngine::PRICE_TIER_TYPES[$tierKey] ?? null;

            if ($definition === null) {
                continue;
            }

            PriceChannelTier::query()
                ->where('price_control_id', $profile->id)
                ->where('tier_key', $tierKey)
                ->update([
                    'sort_order' => $sortOrder,
                    'name_en' => $definition['name_en'],
                    'name_ur' => $definition['name_ur'],
                    'product_field' => $definition['price_field'],
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
                Rule::unique('price_controls', 'control_number')->ignore($ignoreId),
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
    private function validateTier(array $data, PriceControl $profile, ?int $ignoreTierId = null): array
    {
        $validated = validator($data, [
            'tier_key' => [
                'nullable',
                'string',
                'max:64',
                Rule::unique('price_channel_tiers', 'tier_key')
                    ->where('price_control_id', $profile->id)
                    ->ignore($ignoreTierId),
            ],
            'name_en' => ['required', 'string', 'max:120'],
            'name_ur' => ['nullable', 'string', 'max:120'],
            'markup_percent' => ['required', 'numeric', 'min:0', 'max:500'],
            'product_field' => ['required', 'string', 'max:64'],
            'status' => ['required', Rule::in(self::STATUSES)],
        ])->validate();

        if (! filled($validated['tier_key'] ?? null)) {
            $validated['tier_key'] = Str::slug($validated['name_en'], '_');
        }

        if ($validated['tier_key'] === 'retail') {
            throw ValidationException::withMessages([
                'tier_key' => __('price_controls.messages.cannot_edit_retail'),
            ]);
        }

        if (isset(ChannelPricingEngine::TIER_SORT_ORDER[$validated['tier_key']])) {
            $validated['sort_order'] = ChannelPricingEngine::TIER_SORT_ORDER[$validated['tier_key']];
        } else {
            $validated['sort_order'] = (int) $profile->tiers()->max('sort_order') + 1;
        }

        $this->assertProductFieldAvailable($validated['product_field'], $profile->id, $ignoreTierId);

        return $validated;
    }

    private function assertProductFieldAvailable(string $field, int $profileId, ?int $ignoreTierId = null): void
    {
        if ($field === 'retail_price') {
            throw ValidationException::withMessages([
                'product_field' => __('price_controls.messages.retail_field_reserved'),
            ]);
        }

        $query = PriceChannelTier::query()
            ->where('price_control_id', $profileId)
            ->where('product_field', $field);

        if ($ignoreTierId !== null) {
            $query->where('id', '!=', $ignoreTierId);
        }

        if ($query->exists()) {
            throw ValidationException::withMessages([
                'product_field' => __('price_controls.messages.field_in_use'),
            ]);
        }
    }
}
