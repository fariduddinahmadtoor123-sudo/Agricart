<?php

namespace App\Services\Sales;

use App\Models\Category;
use App\Models\OnlineSaleOrder;
use App\Models\OnlineSaleOrderItem;
use App\Models\OnlineSaleOrderSupplierAssignment;
use App\Models\OnlineSaleOrderSupplierAssignmentItem;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

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
        return $this->suggestSuppliersForItemIds($order, $order->items->pluck('id')->map(fn ($id): int => (int) $id)->all());
    }

    /**
     * @param  list<int>  $itemIds
     * @return list<int>
     */
    public function categoryIdsForItemIds(OnlineSaleOrder $order, array $itemIds): array
    {
        $order->loadMissing(['items.product.category.parent']);
        $wanted = array_fill_keys(array_map('intval', $itemIds), true);
        $ids = [];

        foreach ($order->items as $item) {
            if (! isset($wanted[(int) $item->id])) {
                continue;
            }

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
     * @param  list<int>  $itemIds
     * @return Collection<int, Supplier>
     */
    public function suggestSuppliersForItemIds(OnlineSaleOrder $order, array $itemIds): Collection
    {
        $categoryIds = $this->categoryIdsForItemIds($order, $itemIds);

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
     * @return Collection<int, Supplier>
     */
    public function allActiveSuppliers(): Collection
    {
        return Supplier::query()
            ->where('status', 'active')
            ->orderBy('business_name_en')
            ->get();
    }

    /**
     * Item IDs already linked to a supplier packing assignment (any status except declined).
     *
     * @return list<int>
     */
    public function assignedItemIds(OnlineSaleOrder $order): array
    {
        return OnlineSaleOrderSupplierAssignmentItem::query()
            ->whereHas('assignment', function ($query) use ($order): void {
                $query->where('online_sale_order_id', $order->id)
                    ->where('status', '!=', OnlineSaleOrderSupplierAssignment::STATUS_DECLINED);
            })
            ->pluck('online_sale_order_item_id')
            ->map(fn ($id): int => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @return array{total: int, sent: int, remaining: int, complete: bool}
     */
    public function packingProgress(OnlineSaleOrder $order): array
    {
        $order->loadMissing('items');
        $total = $order->items->count();
        $sent = count($this->assignedItemIds($order));
        $remaining = max(0, $total - $sent);

        return [
            'total' => $total,
            'sent' => min($sent, $total),
            'remaining' => $remaining,
            'complete' => $total > 0 && $remaining === 0,
        ];
    }

    /**
     * @return list<array{item_id: int, label: string, quantity: string, sent: bool, supplier_name: string|null}>
     */
    public function packingLineStates(OnlineSaleOrder $order): array
    {
        $order->loadMissing([
            'items.product',
            'supplierAssignments.supplier',
            'supplierAssignments.lines',
        ]);

        $itemSupplier = [];

        foreach ($order->supplierAssignments as $assignment) {
            if ($assignment->status === OnlineSaleOrderSupplierAssignment::STATUS_DECLINED) {
                continue;
            }

            $name = $assignment->supplier?->displayBusinessName();

            foreach ($assignment->lines as $line) {
                $itemSupplier[(int) $line->online_sale_order_item_id] = filled($name) ? (string) $name : null;
            }
        }

        $rows = [];

        foreach ($order->items->sortBy('sort_order') as $item) {
            $itemId = (int) $item->id;
            $sent = array_key_exists($itemId, $itemSupplier);

            $rows[] = [
                'item_id' => $itemId,
                'label' => $item->displayName($order->locale),
                'quantity' => (string) ($item->quantity ?? '1'),
                'sent' => $sent,
                'supplier_name' => $sent ? ($itemSupplier[$itemId] ?? null) : null,
            ];
        }

        return $rows;
    }

    /**
     * Attach selected order lines to a supplier and mark the assignment sent.
     * Order remains in the dispatch queue; partial sends leave remaining lines open.
     *
     * @param  list<int>  $itemIds
     */
    public function assignItemsToSupplierAndMarkSent(
        OnlineSaleOrder $order,
        int $supplierId,
        array $itemIds,
        ?User $actor = null,
    ): OnlineSaleOrderSupplierAssignment {
        $itemIds = array_values(array_unique(array_filter(array_map('intval', $itemIds))));

        if ($itemIds === []) {
            throw ValidationException::withMessages([
                'items' => [__('order_dispatch.validation.select_items_to_forward')],
            ]);
        }

        $order->loadMissing('items');
        $validIds = $order->items->pluck('id')->map(fn ($id): int => (int) $id)->all();

        foreach ($itemIds as $itemId) {
            if (! in_array($itemId, $validIds, true)) {
                throw ValidationException::withMessages([
                    'items' => [__('order_dispatch.validation.invalid_forward_items')],
                ]);
            }
        }

        $alreadyAssigned = array_intersect($itemIds, $this->assignedItemIds($order));

        if ($alreadyAssigned !== []) {
            throw ValidationException::withMessages([
                'items' => [__('order_dispatch.validation.items_already_forwarded')],
            ]);
        }

        $supplier = Supplier::query()->whereKey($supplierId)->where('status', 'active')->first();

        if ($supplier === null) {
            throw ValidationException::withMessages([
                'supplier' => [__('order_dispatch.validation.supplier_required')],
            ]);
        }

        $assignment = $order->supplierAssignments()->where('supplier_id', $supplierId)->first();

        if ($assignment === null) {
            $assignment = OnlineSaleOrderSupplierAssignment::query()->create([
                'online_sale_order_id' => $order->id,
                'supplier_id' => $supplierId,
                'status' => OnlineSaleOrderSupplierAssignment::STATUS_PENDING,
                'assigned_by_id' => $actor?->id,
            ]);
        }

        foreach ($itemIds as $itemId) {
            OnlineSaleOrderSupplierAssignmentItem::query()->firstOrCreate([
                'online_sale_order_supplier_assignment_id' => $assignment->id,
                'online_sale_order_item_id' => $itemId,
            ]);
        }

        if (in_array($order->fulfillment_status, [OnlineSaleOrder::FULFILLMENT_AWAITING, ''], true)
            || $order->fulfillment_status === null) {
            $order->fulfillment_status = OnlineSaleOrder::FULFILLMENT_SOURCING;
            $order->save();
        }

        return $this->markAssignmentSent($assignment->fresh(['lines', 'supplier']));
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
                // Free line unique so another supplier can take the items.
                $assignment->lines()->delete();
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

    /**
     * Ensure a supplier assignment exists, attach packing lines, and mark sent.
     *
     * @param  list<int>  $itemIds  Empty = all remaining unassigned lines
     */
    public function ensureAndMarkSupplierSent(
        OnlineSaleOrder $order,
        int $supplierId,
        ?User $actor = null,
        array $itemIds = [],
    ): ?OnlineSaleOrderSupplierAssignment {
        $order->loadMissing('items');

        if ($itemIds === []) {
            $assigned = $this->assignedItemIds($order);
            $itemIds = $order->items
                ->pluck('id')
                ->map(fn ($id): int => (int) $id)
                ->reject(fn (int $id): bool => in_array($id, $assigned, true))
                ->values()
                ->all();
        }

        if ($itemIds === []) {
            throw ValidationException::withMessages([
                'items' => [__('order_dispatch.validation.select_items_to_forward')],
            ]);
        }

        return $this->assignItemsToSupplierAndMarkSent($order, $supplierId, $itemIds, $actor);
    }

    /**
     * @param  list<int>  $itemIds
     */
    public function whatsAppUrlForSupplier(
        OnlineSaleOrder $order,
        Supplier $supplier,
        string $document = 'packing',
        array $itemIds = [],
    ): ?string {
        $digits = $this->normalizePhoneDigits($supplier->primaryMobile());

        if ($digits === null) {
            return null;
        }

        $text = $this->forwardMessageText(
            $order,
            $document,
            $supplier->displayBusinessName(),
            $supplier->id,
            null,
            $itemIds,
        );

        return 'https://api.whatsapp.com/send?phone=' . $digits . '&text=' . rawurlencode($text);
    }

    /**
     * @param  list<int>  $itemIds
     */
    public function mailToUrlForSupplier(
        OnlineSaleOrder $order,
        Supplier $supplier,
        string $document = 'packing',
        array $itemIds = [],
    ): ?string {
        if (! filled($supplier->email)) {
            return null;
        }

        $subject = __('order_dispatch.email.subject', ['number' => $order->order_number]);
        $body = $this->forwardMessageText(
            $order,
            $document,
            $supplier->displayBusinessName(),
            $supplier->id,
            null,
            $itemIds,
        );

        return 'mailto:' . rawurlencode((string) $supplier->email)
            . '?subject=' . rawurlencode($subject)
            . '&body=' . rawurlencode($body);
    }

    public function whatsAppUrlForStaff(OnlineSaleOrder $order, User $staff, string $document = 'packing'): ?string
    {
        $digits = $this->normalizePhoneDigits($this->primaryPhoneForUser($staff));

        if ($digits === null) {
            return null;
        }

        $text = $this->forwardMessageText($order, $document, (string) $staff->name, null, $staff->id);

        return 'https://api.whatsapp.com/send?phone=' . $digits . '&text=' . rawurlencode($text);
    }

    public function mailToUrlForStaff(OnlineSaleOrder $order, User $staff, string $document = 'packing'): ?string
    {
        if (! filled($staff->email)) {
            return null;
        }

        $subject = __('order_dispatch.email.subject', ['number' => $order->order_number]);
        $body = $this->forwardMessageText($order, $document, (string) $staff->name, null, $staff->id);

        return 'mailto:' . rawurlencode((string) $staff->email)
            . '?subject=' . rawurlencode($subject)
            . '&body=' . rawurlencode($body);
    }

    public function primaryPhoneForUser(User $user): ?string
    {
        $phones = is_array($user->phone_numbers) ? $user->phone_numbers : [];

        foreach ($phones as $row) {
            if (! is_array($row)) {
                continue;
            }

            $phone = trim((string) ($row['phone_number'] ?? ''));

            if ($phone !== '') {
                return $phone;
            }
        }

        return null;
    }

    public function normalizePhoneDigits(?string $phone): ?string
    {
        if (! filled($phone)) {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $phone) ?? '';

        if ($digits === '') {
            return null;
        }

        // 0092… → 92…
        if (str_starts_with($digits, '00')) {
            $digits = substr($digits, 2);
        }

        if (str_starts_with($digits, '0')) {
            $digits = '92' . substr($digits, 1);
        }

        if (! str_starts_with($digits, '92') && strlen($digits) === 10) {
            $digits = '92' . $digits;
        }

        // Pakistan mobile: 92 + 10 digits (e.g. 923001112233)
        if (str_starts_with($digits, '92') && strlen($digits) >= 12 && strlen($digits) <= 15) {
            return $digits;
        }

        // Other international numbers already with country code
        if (! str_starts_with($digits, '0') && strlen($digits) >= 10 && strlen($digits) <= 15) {
            return $digits;
        }

        return null;
    }

    /**
     * Print URL for invoice or packing list, optionally addressed to a recipient.
     *
     * @param  list<int>  $itemIds
     */
    public function documentUrl(
        OnlineSaleOrder $order,
        string $document = 'packing',
        ?int $supplierId = null,
        ?int $staffId = null,
        array $itemIds = [],
    ): string {
        $params = ['order' => $order, 'autoprint' => 0];

        if ($supplierId !== null) {
            $params['supplier_id'] = $supplierId;
        }

        if ($staffId !== null) {
            $params['staff_id'] = $staffId;
        }

        $itemIds = array_values(array_unique(array_filter(array_map('intval', $itemIds))));

        if ($itemIds !== []) {
            $params['item_ids'] = implode(',', $itemIds);
        }

        if ($document === 'invoice') {
            return route('admin.online-sale-orders.print', $params);
        }

        return route('admin.online-sale-orders.packing-slip', $params);
    }

    public function supplierMessageText(OnlineSaleOrder $order, Supplier $supplier): string
    {
        return $this->forwardMessageText(
            $order,
            'packing',
            $supplier->displayBusinessName(),
            $supplier->id,
            null,
        );
    }

    /**
     * @param  list<int>  $itemIds
     */
    public function forwardMessageText(
        OnlineSaleOrder $order,
        string $document,
        string $recipientName,
        ?int $supplierId = null,
        ?int $staffId = null,
        array $itemIds = [],
    ): string {
        $url = $this->documentUrl($order, $document, $supplierId, $staffId, $itemIds);

        if ($document === 'invoice') {
            return $this->invoicePlainText($order, $recipientName, $url);
        }

        return $this->packingSlipPlainText($order, $recipientName, $url, $itemIds);
    }

    /**
     * Warehouse / supplier message: products and quantities only (no customer, rates, or address).
     *
     * @param  list<int>  $itemIds
     */
    public function packingSlipPlainText(
        OnlineSaleOrder $order,
        ?string $recipientName = null,
        ?string $documentUrl = null,
        array $itemIds = [],
    ): string {
        $order->loadMissing(['items']);
        $items = $order->items;
        $wanted = array_fill_keys(array_map('intval', $itemIds), true);

        if ($wanted !== []) {
            $items = $items->filter(fn (OnlineSaleOrderItem $item): bool => isset($wanted[(int) $item->id]));
        }

        $lines = [];

        if (filled($recipientName)) {
            $lines[] = __('order_dispatch.messages.supplier_intro', [
                'supplier' => $recipientName,
                'number' => $order->order_number,
            ]);
        } else {
            $lines[] = __('order_dispatch.packing_slip.plain_intro', ['number' => $order->order_number]);
        }

        $lines[] = '';

        foreach ($items as $item) {
            $lines[] = sprintf(
                '• %s × %s',
                $item->displayName($order->locale),
                $item->quantity ?? '1',
            );
        }

        $lines[] = '';
        $lines[] = __('order_dispatch.packing_slip.plain_footer');

        if (filled($documentUrl)) {
            $lines[] = '';
            $lines[] = $documentUrl;
        }

        return implode("\n", $lines);
    }

    public function invoicePlainText(OnlineSaleOrder $order, string $recipientName, string $documentUrl): string
    {
        $order->loadMissing(['items']);

        $lines = [
            __('order_dispatch.messages.invoice_intro', [
                'name' => $recipientName,
                'number' => $order->order_number,
            ]),
            '',
        ];

        foreach ($order->items as $item) {
            $lines[] = sprintf(
                '• %s × %s = %s',
                $item->displayName($order->locale),
                $item->quantity ?? '1',
                number_format((float) $item->line_total, 2),
            );
        }

        $lines[] = '';
        $lines[] = __('order_dispatch.messages.invoice_total', [
            'amount' => number_format((float) $order->total_amount, 2),
        ]);
        $lines[] = '';
        $lines[] = __('order_dispatch.messages.invoice_link');
        $lines[] = $documentUrl;

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
