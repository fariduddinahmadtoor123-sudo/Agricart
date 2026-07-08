<?php

namespace App\Services\Sales;

use App\Models\Category;
use App\Models\OnlineSaleOrder;
use App\Models\OnlineSaleOrderSupplierAssignment;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Support\Collection;

class OnlineOrderDispatchService
{
    /**
     * @return list<int>
     */
    public function categoryIdsForOrder(OnlineSaleOrder $order): array
    {
        $order->loadMissing(['items.product.category.parent']);

        $ids = [];

        foreach ($order->items as $item) {
            $product = $item->product;

            if ($product === null) {
                continue;
            }

            foreach ($this->categoryIdsWithAncestors($product->category) as $categoryId) {
                $ids[] = $categoryId;
            }
        }

        return array_values(array_unique($ids));
    }

    /**
     * @return Collection<int, Supplier>
     */
    public function suggestSuppliersForOrder(OnlineSaleOrder $order): Collection
    {
        $categoryIds = $this->categoryIdsForOrder($order);

        if ($categoryIds === []) {
            return collect();
        }

        return Supplier::query()
            ->where('status', 'active')
            ->whereHas('categories', function ($query) use ($categoryIds): void {
                $query->whereIn('categories.id', $categoryIds);
            })
            ->orderBy('business_name_en')
            ->get();
    }

    /**
     * @param  list<int>  $supplierIds
     */
    public function syncSupplierAssignments(OnlineSaleOrder $order, array $supplierIds, ?User $actor = null): void
    {
        $supplierIds = array_values(array_unique(array_filter(array_map('intval', $supplierIds))));

        $existing = $order->supplierAssignments()->pluck('supplier_id')->all();

        foreach ($supplierIds as $supplierId) {
            if (in_array($supplierId, $existing, true)) {
                continue;
            }

            OnlineSaleOrderSupplierAssignment::query()->create([
                'online_sale_order_id' => $order->id,
                'supplier_id' => $supplierId,
                'status' => OnlineSaleOrderSupplierAssignment::STATUS_PENDING,
                'assigned_by_id' => $actor?->id,
            ]);
        }

        $order->supplierAssignments()
            ->where('status', OnlineSaleOrderSupplierAssignment::STATUS_PENDING)
            ->whereNotIn('supplier_id', $supplierIds === [] ? [-1] : $supplierIds)
            ->delete();

        if ($supplierIds !== [] && $order->fulfillment_status === OnlineSaleOrder::FULFILLMENT_AWAITING) {
            $order->fulfillment_status = OnlineSaleOrder::FULFILLMENT_SOURCING;
            $order->save();
        }
    }

    /**
     * @param  array<int|string, array<string, mixed>>  $rowsBySupplier
     * @param  array<int|string, \Illuminate\Http\UploadedFile|\Livewire\Features\SupportFileUploads\TemporaryUploadedFile|null>  $proofUploads
     */
    public function applySupplierFulfillmentUpdates(
        OnlineSaleOrder $order,
        array $rowsBySupplier,
        array $proofUploads = [],
    ): void {
        $order->loadMissing('supplierAssignments');

        foreach ($rowsBySupplier as $supplierId => $row) {
            if (! is_array($row)) {
                continue;
            }

            $supplierId = (int) $supplierId;
            $assignment = $order->supplierAssignments->firstWhere('supplier_id', $supplierId);

            if ($assignment === null) {
                continue;
            }

            $status = (string) ($row['status'] ?? $assignment->status);

            if (! in_array($status, OnlineSaleOrderSupplierAssignment::ADMIN_STATUSES, true)
                && $status !== OnlineSaleOrderSupplierAssignment::STATUS_SENT) {
                $status = $assignment->status;
            }

            if ($status === OnlineSaleOrderSupplierAssignment::STATUS_SHIPPED) {
                $tracking = trim((string) ($row['tracking_number'] ?? ''));
                $reference = trim((string) ($row['shipping_reference'] ?? ''));
                $hasExistingProof = filled($assignment->shipping_proof_path);
                $upload = $proofUploads[$supplierId] ?? $proofUploads[(string) $supplierId] ?? null;

                if ($tracking === '' && $reference === '' && ! $hasExistingProof && $upload === null) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'supplier_fulfillment' => [__('order_dispatch.validation.shipped_proof_required')],
                    ]);
                }
            }

            $assignment->status = $status;
            $assignment->carrier_name = $this->nullableString($row['carrier_name'] ?? null);
            $assignment->tracking_number = $this->nullableString($row['tracking_number'] ?? null);
            $assignment->shipping_reference = $this->nullableString($row['shipping_reference'] ?? null);
            $assignment->supplier_response_notes = $this->nullableString($row['supplier_response_notes'] ?? null);

            if ($status === OnlineSaleOrderSupplierAssignment::STATUS_ACKNOWLEDGED && $assignment->acknowledged_at === null) {
                $assignment->acknowledged_at = now();
            }

            if ($status === OnlineSaleOrderSupplierAssignment::STATUS_SHIPPED) {
                if ($assignment->shipped_at === null) {
                    $assignment->shipped_at = now();
                }

                if ($upload !== null) {
                    $path = $upload->store('online-orders/shipping-proofs/' . $order->id, 'public');
                    $assignment->shipping_proof_path = $path;
                }
            }

            if ($status === OnlineSaleOrderSupplierAssignment::STATUS_DECLINED) {
                $assignment->shipped_at = null;
            }

            $assignment->save();
        }

        $this->syncOrderFulfillmentFromSuppliers($order->fresh('supplierAssignments'));
    }

    public function syncOrderFulfillmentFromSuppliers(OnlineSaleOrder $order): void
    {
        $assignments = $order->supplierAssignments;

        if ($assignments->isEmpty()) {
            return;
        }

        $statuses = $assignments->pluck('status')->all();

        if (in_array(OnlineSaleOrderSupplierAssignment::STATUS_SHIPPED, $statuses, true)) {
            if ($order->fulfillment_status !== OnlineSaleOrder::FULFILLMENT_DELIVERED) {
                $order->fulfillment_status = OnlineSaleOrder::FULFILLMENT_TRANSIT;
            }
        } elseif (in_array(OnlineSaleOrderSupplierAssignment::STATUS_ACKNOWLEDGED, $statuses, true)
            || in_array(OnlineSaleOrderSupplierAssignment::STATUS_SENT, $statuses, true)) {
            if (in_array($order->fulfillment_status, [
                OnlineSaleOrder::FULFILLMENT_AWAITING,
                OnlineSaleOrder::FULFILLMENT_ASSIGNED,
            ], true)) {
                $order->fulfillment_status = OnlineSaleOrder::FULFILLMENT_SOURCING;
            }
        }

        $order->save();
    }

    private function nullableString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $trimmed = trim((string) $value);

        return $trimmed === '' ? null : $trimmed;
    }

    public function markAssignmentSent(OnlineSaleOrderSupplierAssignment $assignment): OnlineSaleOrderSupplierAssignment
    {
        $assignment->status = OnlineSaleOrderSupplierAssignment::STATUS_SENT;
        $assignment->sent_at = now();
        $assignment->save();

        return $assignment->fresh(['supplier', 'order']);
    }

    public function whatsAppUrlForSupplier(OnlineSaleOrder $order, Supplier $supplier): ?string
    {
        $phone = $supplier->primaryMobile();

        if (! filled($phone)) {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $phone) ?? '';

        if ($digits === '') {
            return null;
        }

        if (str_starts_with($digits, '0')) {
            $digits = '92' . substr($digits, 1);
        }

        if (! str_starts_with($digits, '92') && strlen($digits) === 10) {
            $digits = '92' . $digits;
        }

        $text = $this->supplierMessageText($order, $supplier);

        return 'https://wa.me/' . $digits . '?text=' . rawurlencode($text);
    }

    public function mailToUrlForSupplier(OnlineSaleOrder $order, Supplier $supplier): ?string
    {
        if (! filled($supplier->email)) {
            return null;
        }

        $subject = __('order_dispatch.email.subject', ['number' => $order->order_number]);
        $body = $this->supplierMessageText($order, $supplier);

        return 'mailto:' . rawurlencode((string) $supplier->email)
            . '?subject=' . rawurlencode($subject)
            . '&body=' . rawurlencode($body);
    }

    public function supplierMessageText(OnlineSaleOrder $order, Supplier $supplier): string
    {
        return $this->packingSlipPlainText($order);
    }

    /**
     * Warehouse / supplier message: products and quantities only (no customer, rates, or address).
     */
    public function packingSlipPlainText(OnlineSaleOrder $order): string
    {
        $order->loadMissing(['items']);

        $lines = [
            __('order_dispatch.packing_slip.plain_intro', ['number' => $order->order_number]),
            '',
        ];

        foreach ($order->items as $item) {
            $lines[] = sprintf(
                '• %s × %s',
                $item->displayName($order->locale),
                $item->quantity ?? '1',
            );
        }

        $lines[] = '';
        $lines[] = __('order_dispatch.packing_slip.plain_footer');

        return implode("\n", $lines);
    }

    public function enterDispatchQueueIfPaid(OnlineSaleOrder $order): void
    {
        if (! in_array($order->payment_status, ['paid', 'partial'], true)) {
            return;
        }

        if ($order->order_status === OnlineSaleOrder::STATUS_CANCELLED) {
            return;
        }

        if ($order->order_status === OnlineSaleOrder::STATUS_PENDING && $order->payment_status === 'paid') {
            $order->order_status = OnlineSaleOrder::STATUS_PAYMENT_RECEIVED;
        }

        if ($order->fulfillment_status === '' || $order->fulfillment_status === null) {
            $order->fulfillment_status = OnlineSaleOrder::FULFILLMENT_AWAITING;
        }

        $order->save();
    }

    /**
     * @return list<int>
     */
    private function categoryIdsWithAncestors(?Category $category): array
    {
        $ids = [];

        while ($category !== null) {
            $ids[] = $category->id;
            $category = $category->parent;
        }

        return $ids;
    }
}
