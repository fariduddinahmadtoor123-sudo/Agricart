<?php

namespace App\Services\Logistics;

use App\Enums\ShippingChargeMode;
use App\Models\DeliveryMethod;
use App\Models\Product;
use App\Services\Sales\SaleService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class DeliveryMethodService
{
    /**
     * Storefront product-page cards for active methods assigned to the product.
     *
     * @return list<array{
     *     id: int,
     *     name: string,
     *     description: ?string,
     *     notice: ?string,
     *     shipping_mode: ?string,
     *     shipping_mode_label: ?string,
     *     payments: list<string>,
     *     payment_labels: list<string>,
     *     cod_min_advance_percent: float,
     *     full_advance_required: bool,
     * }>
     */
    public function optionsForProductPage(int $productId): array
    {
        return $this->activeForCheckout([$productId])
            ->map(function (DeliveryMethod $method): array {
                $payments = $this->paymentMethodsFor($method);
                $mode = $method->rule?->shipping_charge_mode?->value;

                return [
                    'id' => $method->id,
                    'name' => $method->localizedName(),
                    'description' => $method->localizedDescription(),
                    'notice' => $method->rule?->localizedNotice(),
                    'shipping_mode' => $mode,
                    'shipping_mode_label' => filled($mode)
                        ? __('agricart.storefront.checkout.shipping_modes.'.$mode)
                        : null,
                    'payments' => $payments,
                    'payment_labels' => array_values(array_map(
                        static fn (string $payment): string => __('pos.payment_methods.'.$payment),
                        $payments,
                    )),
                    'cod_min_advance_percent' => (float) ($method->rule?->cod_min_advance_percent ?? 0),
                    'full_advance_required' => (bool) ($method->rule?->full_advance_required ?? false),
                ];
            })
            ->filter(static fn (array $option): bool => $option['payments'] !== [])
            ->values()
            ->all();
    }

    /**
     * @param  list<int>|null  $productIds
     * @return Collection<int, DeliveryMethod>
     */
    public function activeForCheckout(?array $productIds = null): Collection
    {
        $methods = DeliveryMethod::query()
            ->with('rule')
            ->where('status', DeliveryMethod::STATUS_ACTIVE)
            ->orderBy('sort_order')
            ->orderBy('name_en')
            ->get();

        if ($productIds === null) {
            return $methods;
        }

        return $this->intersectForProducts($methods, $productIds);
    }

    /**
     * @param  Collection<int, DeliveryMethod>  $methods
     * @param  list<int>  $productIds
     * @return Collection<int, DeliveryMethod>
     */
    public function intersectForProducts(Collection $methods, array $productIds): Collection
    {
        $productIds = array_values(array_unique(array_filter(
            array_map(static fn ($id): int => (int) $id, $productIds),
            static fn (int $id): bool => $id > 0,
        )));

        if ($productIds === []) {
            return collect();
        }

        $allowedSets = [];

        foreach ($productIds as $productId) {
            $allowedSets[] = DB::table('product_delivery_method')
                ->where('product_id', $productId)
                ->pluck('delivery_method_id')
                ->map(static fn ($id): int => (int) $id)
                ->all();
        }

        $intersection = array_shift($allowedSets) ?? [];

        foreach ($allowedSets as $set) {
            $intersection = array_values(array_intersect($intersection, $set));
        }

        if ($intersection === []) {
            return collect();
        }

        return $methods
            ->filter(static fn (DeliveryMethod $method): bool => in_array($method->id, $intersection, true))
            ->values();
    }

    /**
     * @param  list<int>  $productIds
     */
    public function assertAvailableForProducts(DeliveryMethod $method, array $productIds): void
    {
        $allowed = $this->activeForCheckout($productIds);

        if ($allowed->contains(static fn (DeliveryMethod $item): bool => $item->id === $method->id)) {
            return;
        }

        throw ValidationException::withMessages([
            'delivery_method_id' => [__('agricart.storefront.checkout.no_shared_delivery_methods')],
        ]);
    }

    /**
     * @param  list<int>  $productIds
     */
    public function productsMissingDeliveryLinks(array $productIds): bool
    {
        $productIds = array_values(array_unique(array_filter(
            array_map(static fn ($id): int => (int) $id, $productIds),
            static fn (int $id): bool => $id > 0,
        )));

        if ($productIds === []) {
            return true;
        }

        foreach ($productIds as $productId) {
            $hasLink = DB::table('product_delivery_method')
                ->where('product_id', $productId)
                ->exists();

            if (! $hasLink) {
                return true;
            }
        }

        return false;
    }

    public function findActive(int $id): ?DeliveryMethod
    {
        return DeliveryMethod::query()
            ->with('rule')
            ->where('status', DeliveryMethod::STATUS_ACTIVE)
            ->find($id);
    }

    /**
     * Attach every active delivery method to products that have no delivery links yet.
     * Does not change products that already have at least one method assigned.
     */
    public function attachActiveMethodsToUnlinkedProducts(): int
    {
        $activeMethodIds = DeliveryMethod::query()
            ->where('status', DeliveryMethod::STATUS_ACTIVE)
            ->pluck('id')
            ->map(static fn ($id): int => (int) $id)
            ->all();

        if ($activeMethodIds === []) {
            return 0;
        }

        $linkedProductIds = DB::table('product_delivery_method')
            ->distinct()
            ->pluck('product_id')
            ->map(static fn ($id): int => (int) $id)
            ->all();

        $attached = 0;
        $now = now();

        Product::query()
            ->when($linkedProductIds !== [], fn ($query) => $query->whereNotIn('id', $linkedProductIds))
            ->orderBy('id')
            ->chunkById(200, function ($products) use ($activeMethodIds, $now, &$attached): void {
                $rows = [];

                foreach ($products as $product) {
                    foreach ($activeMethodIds as $methodId) {
                        $rows[] = [
                            'product_id' => $product->id,
                            'delivery_method_id' => $methodId,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ];
                    }
                    $attached++;
                }

                if ($rows !== []) {
                    DB::table('product_delivery_method')->insert($rows);
                }
            });

        return $attached;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): DeliveryMethod
    {
        $validated = $this->validate($data);

        return DB::transaction(function () use ($validated): DeliveryMethod {
            $method = DeliveryMethod::query()->create($validated['method']);
            $method->rule()->create($validated['rules']);

            return $method->load('rule');
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(DeliveryMethod $method, array $data): DeliveryMethod
    {
        $validated = $this->validate($data, $method->id);

        return DB::transaction(function () use ($method, $validated): DeliveryMethod {
            $method->update($validated['method']);
            $method->rule()->updateOrCreate(
                ['delivery_method_id' => $method->id],
                $validated['rules'],
            );

            return $method->fresh(['rule']);
        });
    }

    public function delete(DeliveryMethod $method): void
    {
        $method->delete();
    }

    /**
     * @return list<string>
     */
    public function paymentMethodsFor(DeliveryMethod $method): array
    {
        $rule = $method->rule;
        $allowed = $rule?->allowedPaymentMethods() ?? [];

        if ($allowed === []) {
            $allowed = SaleService::PAYMENT_METHODS;
        }

        $allowed = array_values(array_intersect($allowed, SaleService::PAYMENT_METHODS));

        if ($rule !== null && ! $rule->cod_allowed) {
            $allowed = array_values(array_filter(
                $allowed,
                static fn (string $method): bool => $method !== 'cash',
            ));
        }

        return $allowed;
    }

    public function checkoutShippingAmount(DeliveryMethod $method): float
    {
        $rule = $method->rule;

        if ($rule === null || $rule->shipping_charge_mode !== ShippingChargeMode::CollectUpfront) {
            return 0.0;
        }

        return max(0.0, (float) ($rule->shipping_fee ?? 0));
    }

    /**
     * @throws ValidationException
     */
    public function assertFullAdvanceCheckoutRequirements(
        DeliveryMethod $method,
        ?string $paymentMethod,
        ?string $paymentReference,
        bool $hasReceiptUpload,
    ): void {
        if (! (bool) ($method->rule?->full_advance_required ?? false)) {
            return;
        }

        if ($paymentMethod !== 'bank_transfer') {
            throw ValidationException::withMessages([
                'customer_payment_method' => [__('agricart.storefront.checkout.full_advance_requires_bank_transfer')],
            ]);
        }

        if (! filled($paymentReference) && ! $hasReceiptUpload) {
            throw ValidationException::withMessages([
                'customer_payment_reference' => [__('agricart.storefront.checkout.full_advance_proof_required')],
            ]);
        }
    }

    /**
     * @throws ValidationException
     */
    public function assertCheckoutSelection(DeliveryMethod $method, string $paymentMethod): void
    {
        if (! $method->isActive()) {
            throw ValidationException::withMessages([
                'delivery_method_id' => [__('agricart.storefront.checkout.delivery_inactive')],
            ]);
        }

        $allowed = $this->paymentMethodsFor($method);

        if (! in_array($paymentMethod, $allowed, true)) {
            throw ValidationException::withMessages([
                'customer_payment_method' => [__('agricart.storefront.checkout.payment_not_allowed_for_delivery')],
            ]);
        }
    }

    /**
     * Snapshot fields for OnlineSaleOrder.
     *
     * @return array<string, mixed>
     */
    public function orderSnapshot(DeliveryMethod $method): array
    {
        $rule = $method->rule;

        return [
            'delivery_method_id' => $method->id,
            'delivery_method_name_en' => $method->name_en,
            'delivery_method_name_ur' => $method->name_ur,
            'shipping_charge_mode' => $rule?->shipping_charge_mode?->value,
            'cod_min_advance_percent' => $rule?->cod_min_advance_percent,
            'full_advance_required' => $rule?->full_advance_required,
            'delivery_customer_notice_en' => $rule?->customer_notice_en,
            'delivery_customer_notice_ur' => $rule?->customer_notice_ur,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{method: array<string, mixed>, rules: array<string, mixed>}
     */
    private function validate(array $data, ?int $ignoreId = null): array
    {
        $validated = validator($data, [
            'name_en' => ['required', 'string', 'max:255'],
            'name_ur' => ['required', 'string', 'max:255'],
            'description_en' => ['nullable', 'string', 'max:2000'],
            'description_ur' => ['nullable', 'string', 'max:2000'],
            'slug' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('delivery_methods', 'slug')->ignore($ignoreId),
            ],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'status' => ['required', 'string', Rule::in([DeliveryMethod::STATUS_ACTIVE, DeliveryMethod::STATUS_INACTIVE])],
            'allowed_payment_methods' => ['nullable', 'array'],
            'allowed_payment_methods.*' => ['string', Rule::in(SaleService::PAYMENT_METHODS)],
            'cod_allowed' => ['boolean'],
            'cod_min_advance_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'full_advance_required' => ['boolean'],
            'customer_notice_en' => ['nullable', 'string', 'max:5000'],
            'customer_notice_ur' => ['nullable', 'string', 'max:5000'],
            'shipping_charge_mode' => ['required', 'string', Rule::in(ShippingChargeMode::values())],
            'shipping_fee' => ['nullable', 'numeric', 'min:0'],
        ])->validate();

        $slug = filled($validated['slug'] ?? null)
            ? Str::slug((string) $validated['slug'])
            : $this->uniqueSlug(Str::slug((string) $validated['name_en']), $ignoreId);

        if ($slug === '') {
            $slug = $this->uniqueSlug('delivery-method', $ignoreId);
        }

        $payments = array_values(array_unique($validated['allowed_payment_methods'] ?? SaleService::PAYMENT_METHODS));

        if ($payments === []) {
            $payments = SaleService::PAYMENT_METHODS;
        }

        $codAllowed = (bool) ($validated['cod_allowed'] ?? true);
        $effectivePayments = $payments;

        if (! $codAllowed) {
            $effectivePayments = array_values(array_filter(
                $effectivePayments,
                static fn (string $method): bool => $method !== 'cash',
            ));
        }

        if ($effectivePayments === []) {
            throw ValidationException::withMessages([
                'allowed_payment_methods' => [__('agricart.delivery_methods.messages.payment_methods_required')],
            ]);
        }

        return [
            'method' => [
                'name_en' => trim((string) $validated['name_en']),
                'name_ur' => trim((string) $validated['name_ur']),
                'description_en' => $validated['description_en'] ?? null,
                'description_ur' => $validated['description_ur'] ?? null,
                'slug' => $slug,
                'sort_order' => (int) ($validated['sort_order'] ?? 0),
                'status' => $validated['status'],
            ],
            'rules' => [
                'allowed_payment_methods' => $payments,
                'cod_allowed' => $codAllowed,
                'cod_min_advance_percent' => (float) ($validated['cod_min_advance_percent'] ?? 0),
                'full_advance_required' => (bool) ($validated['full_advance_required'] ?? false),
                'customer_notice_en' => $validated['customer_notice_en'] ?? null,
                'customer_notice_ur' => $validated['customer_notice_ur'] ?? null,
                'shipping_charge_mode' => $validated['shipping_charge_mode'],
                'shipping_fee' => (float) ($validated['shipping_fee'] ?? 0),
            ],
        ];
    }

    private function uniqueSlug(string $base, ?int $ignoreId = null): string
    {
        $base = $base !== '' ? $base : 'delivery-method';
        $slug = $base;
        $i = 2;

        while (
            DeliveryMethod::query()
                ->when($ignoreId !== null, fn ($q) => $q->where('id', '!=', $ignoreId))
                ->where('slug', $slug)
                ->exists()
        ) {
            $slug = $base.'-'.$i;
            $i++;
        }

        return $slug;
    }
}
