<?php

namespace App\Services\Sales;

use App\Models\OnlineOrderReturnRequest;
use App\Models\OnlineOrderReturnRequestItem;
use App\Models\OnlineSaleOrder;
use App\Models\OnlineSaleOrderItem;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SaleReturn;
use App\Models\User;
use App\Services\Identifiers\DocumentCounterService;
use App\Services\Media\OptimizedImageStorage;
use App\Services\Media\PublicMediaMirror;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OnlineOrderReturnRequestService
{
    public const MAX_PHOTO_KB = 2048;

    public function __construct(
        private readonly SaleReturnService $saleReturns,
        private readonly SaleService $sales,
    ) {}

    public function orderIsReturnable(OnlineSaleOrder $order): bool
    {
        return $order->fulfillment_status === OnlineSaleOrder::FULFILLMENT_DELIVERED
            && $order->order_status !== OnlineSaleOrder::STATUS_CANCELLED
            && $order->sale_id !== null;
    }

    public function pendingForOrder(OnlineSaleOrder $order): ?OnlineOrderReturnRequest
    {
        return OnlineOrderReturnRequest::query()
            ->where('online_sale_order_id', $order->id)
            ->where('status', OnlineOrderReturnRequest::STATUS_PENDING)
            ->latest('id')
            ->first();
    }

    /**
     * @return list<OnlineOrderReturnRequest>
     */
    public function historyForOrder(OnlineSaleOrder $order): array
    {
        return OnlineOrderReturnRequest::query()
            ->where('online_sale_order_id', $order->id)
            ->with('items')
            ->orderByDesc('id')
            ->get()
            ->all();
    }

    public function remainingQuantity(OnlineSaleOrderItem $item): float
    {
        $sold = $this->sales->numeric((string) $item->quantity);
        $claimed = (float) OnlineOrderReturnRequestItem::query()
            ->where('online_sale_order_item_id', $item->id)
            ->whereHas('request', function ($query): void {
                $query->whereIn('status', [
                    OnlineOrderReturnRequest::STATUS_PENDING,
                    OnlineOrderReturnRequest::STATUS_APPROVED,
                ]);
            })
            ->sum('quantity');

        return max(0, round($sold - $claimed, 4));
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function submit(OnlineSaleOrder $order, array $payload): OnlineOrderReturnRequest
    {
        if (! $this->orderIsReturnable($order)) {
            throw ValidationException::withMessages([
                'order' => [__('agricart.customer.returns.errors.not_eligible')],
            ]);
        }

        if ($this->pendingForOrder($order) !== null) {
            throw ValidationException::withMessages([
                'order' => [__('agricart.customer.returns.errors.pending_exists')],
            ]);
        }

        $lines = is_array($payload['items'] ?? null) ? $payload['items'] : [];
        $note = trim((string) ($payload['customer_note'] ?? ''));

        if (mb_strlen($note) > 1000) {
            throw ValidationException::withMessages([
                'customer_note' => [__('agricart.customer.returns.errors.note')],
            ]);
        }

        $order->loadMissing('items');
        $itemsById = $order->items->keyBy('id');
        $prepared = [];

        foreach ($lines as $itemId => $line) {
            $item = $itemsById->get((int) $itemId);

            if (! $item instanceof OnlineSaleOrderItem) {
                continue;
            }

            $qty = $this->sales->numeric((string) ($line['quantity'] ?? '0'));

            if ($qty <= 0) {
                continue;
            }

            $remaining = $this->remainingQuantity($item);

            if ($qty > $remaining + 0.0001) {
                throw ValidationException::withMessages([
                    "items.{$itemId}.quantity" => [__('agricart.customer.returns.errors.qty')],
                ]);
            }

            $reason = (string) ($line['reason'] ?? '');

            if (! in_array($reason, OnlineOrderReturnRequestItem::REASONS, true)) {
                throw ValidationException::withMessages([
                    "items.{$itemId}.reason" => [__('agricart.customer.returns.errors.reason')],
                ]);
            }

            $photo = $line['photo'] ?? null;

            if ($photo !== null && ! $photo instanceof UploadedFile) {
                $photo = null;
            }

            $prepared[] = [
                'item' => $item,
                'quantity' => $qty,
                'reason' => $reason,
                'photo' => $photo,
            ];
        }

        if ($prepared === []) {
            throw ValidationException::withMessages([
                'items' => [__('agricart.customer.returns.errors.items_required')],
            ]);
        }

        return DB::transaction(function () use ($order, $note, $prepared): OnlineOrderReturnRequest {
            $request = OnlineOrderReturnRequest::query()->create([
                'request_number' => $this->nextRequestNumber(),
                'online_sale_order_id' => $order->id,
                'customer_id' => $order->customer_id,
                'status' => OnlineOrderReturnRequest::STATUS_PENDING,
                'customer_note' => $note !== '' ? $note : null,
            ]);

            foreach ($prepared as $index => $row) {
                /** @var OnlineSaleOrderItem $item */
                $item = $row['item'];
                $photoPath = null;

                if ($row['photo'] instanceof UploadedFile) {
                    $photoPath = $this->storePhoto($row['photo']);
                }

                OnlineOrderReturnRequestItem::query()->create([
                    'online_order_return_request_id' => $request->id,
                    'online_sale_order_item_id' => $item->id,
                    'product_id' => $item->product_id,
                    'sort_order' => $index,
                    'quantity' => $this->sales->formatQty($row['quantity']),
                    'reason' => $row['reason'],
                    'photo_path' => $photoPath,
                ]);
            }

            return $request->fresh(['items']) ?? $request;
        });
    }

    public function cancel(OnlineOrderReturnRequest $request): void
    {
        if (! $request->isPending()) {
            throw ValidationException::withMessages([
                'request' => [__('agricart.customer.returns.errors.not_pending')],
            ]);
        }

        $request->forceFill([
            'status' => OnlineOrderReturnRequest::STATUS_CANCELLED,
            'reviewed_at' => now(),
        ])->save();
    }

    public function reject(OnlineOrderReturnRequest $request, User $actor, string $note): void
    {
        if (! $request->isPending()) {
            throw ValidationException::withMessages([
                'request' => [__('sale_returns.requests.errors.not_pending')],
            ]);
        }

        $note = trim($note);

        if ($note === '' || mb_strlen($note) > 1000) {
            throw ValidationException::withMessages([
                'rejection_note' => [__('sale_returns.requests.errors.rejection_note')],
            ]);
        }

        $request->forceFill([
            'status' => OnlineOrderReturnRequest::STATUS_REJECTED,
            'rejection_note' => $note,
            'reviewed_by_id' => $actor->id,
            'reviewed_at' => now(),
        ])->save();
    }

    public function approve(OnlineOrderReturnRequest $request, User $actor): SaleReturn
    {
        return DB::transaction(function () use ($request, $actor): SaleReturn {
            $request = OnlineOrderReturnRequest::query()
                ->with(['items.orderItem', 'order.sale.items'])
                ->lockForUpdate()
                ->findOrFail($request->id);

            if (! $request->isPending()) {
                throw ValidationException::withMessages([
                    'request' => [__('sale_returns.requests.errors.not_pending')],
                ]);
            }

            $order = $request->order;
            $sale = $order?->sale;

            if (! $order instanceof OnlineSaleOrder || ! $sale instanceof Sale || ! $sale->isCompleted()) {
                throw ValidationException::withMessages([
                    'request' => [__('sale_returns.requests.errors.sale_missing')],
                ]);
            }

            $rows = $this->saleReturnRows($request, $sale);

            if ($rows === []) {
                throw ValidationException::withMessages([
                    'request' => [__('sale_returns.requests.errors.items_required')],
                ]);
            }

            $paymentMethod = in_array((string) $sale->payment_method, SaleService::PAYMENT_METHODS, true)
                ? (string) $sale->payment_method
                : 'cash';
            $bankAccountId = $paymentMethod === 'bank_transfer' ? $sale->bank_account_id : null;

            if ($paymentMethod === 'bank_transfer' && $bankAccountId === null) {
                $paymentMethod = 'cash';
            }

            $saleReturn = $this->saleReturns->create([
                'sale_id' => $sale->id,
                'customer_id' => $order->customer_id,
                'processed_by_id' => $actor->id,
                'return_date' => now()->toDateString(),
                'payment_status' => 'unpaid',
                'payment_method' => $paymentMethod,
                'bank_account_id' => $bankAccountId,
                'amount_paid' => '0',
                'notes' => $this->approvalNotes($request),
                'rows' => $rows,
            ]);

            $request->forceFill([
                'status' => OnlineOrderReturnRequest::STATUS_APPROVED,
                'sale_return_id' => $saleReturn->id,
                'reviewed_by_id' => $actor->id,
                'reviewed_at' => now(),
            ])->save();

            return $saleReturn;
        });
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function saleReturnRows(OnlineOrderReturnRequest $request, Sale $sale): array
    {
        $saleItems = $sale->items->sortBy('sort_order')->values();
        $used = [];
        $rows = [];

        foreach ($request->items as $line) {
            $qty = $this->sales->numeric((string) $line->quantity);

            if ($qty <= 0) {
                continue;
            }

            $saleItem = $this->matchSaleItem($saleItems, $line, $used);

            if ($saleItem === null) {
                throw ValidationException::withMessages([
                    'request' => [__('sale_returns.requests.errors.sale_line_missing')],
                ]);
            }

            $used[] = $saleItem->id;
            $row = $this->saleReturns->rowFromSaleItem($saleItem);
            $row['return_quantity'] = $this->sales->formatQty($qty);
            $row['return_amount'] = $this->sales->formatMoney(
                $qty * $this->sales->numeric((string) $saleItem->unit_price),
            );
            $rows[] = $row;
        }

        return $rows;
    }

    /**
     * @param  \Illuminate\Support\Collection<int, SaleItem>  $saleItems
     * @param  list<int>  $used
     */
    private function matchSaleItem($saleItems, OnlineOrderReturnRequestItem $line, array $used): ?SaleItem
    {
        $candidates = $saleItems->filter(function (SaleItem $item) use ($line, $used): bool {
            if (in_array($item->id, $used, true)) {
                return false;
            }

            if ($line->product_id !== null) {
                return (int) $item->product_id === (int) $line->product_id;
            }

            return (string) $item->product_name_en === (string) ($line->orderItem?->product_name_en ?? '');
        });

        return $candidates->first();
    }

    private function approvalNotes(OnlineOrderReturnRequest $request): string
    {
        $parts = [
            'Online return '.$request->request_number,
            'order '.($request->order?->order_number ?? ''),
        ];

        if (filled($request->customer_note)) {
            $parts[] = trim((string) $request->customer_note);
        }

        return implode(' — ', array_filter($parts));
    }

    private function storePhoto(UploadedFile $file): string
    {
        try {
            return app(OptimizedImageStorage::class)->store($file, 'return-requests', 'gallery');
        } catch (\Throwable) {
            $path = $file->store('return-requests', 'public');

            if (! is_string($path) || $path === '') {
                throw ValidationException::withMessages([
                    'items' => [__('agricart.customer.returns.errors.photo')],
                ]);
            }

            app(PublicMediaMirror::class)->publish($path, 'public');

            return $path;
        }
    }

    private function nextRequestNumber(): string
    {
        return app(DocumentCounterService::class)->next(
            'online_order_return_requests.request_number',
            'RETQ-',
            5,
            fn (): int => DocumentCounterService::maximumSuffix(
                'online_order_return_requests',
                'request_number',
                'RETQ-',
            ),
        );
    }
}
