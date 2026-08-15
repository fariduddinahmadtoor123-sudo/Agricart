<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\OnlineOrderReturnRequestItem;
use App\Models\OnlineSaleOrder;
use App\Services\Sales\OnlineOrderReturnRequestService;
use App\Services\Sales\SaleService;
use App\Services\Store\StoreLayoutService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CustomerOrderReturnController extends Controller
{
    public function create(
        Request $request,
        string $order,
        StoreLayoutService $layout,
        SaleService $sales,
        OnlineOrderReturnRequestService $returns,
    ): View {
        $model = $this->orderForCustomer($request, $order);
        $pending = $returns->pendingForOrder($model);
        $remaining = [];

        foreach ($model->items as $item) {
            $remaining[$item->id] = $returns->remainingQuantity($item);
        }

        return view('store.orders.return', [
            ...$layout->layoutData(),
            'order' => $model,
            'sales' => $sales,
            'returns' => $returns,
            'pending' => $pending,
            'history' => $returns->historyForOrder($model),
            'remaining' => $remaining,
            'canSubmit' => $returns->orderIsReturnable($model)
                && $pending === null
                && collect($remaining)->contains(fn (float $qty): bool => $qty > 0),
            'reasons' => OnlineOrderReturnRequestItem::REASONS,
        ]);
    }

    public function store(
        Request $request,
        string $order,
        OnlineOrderReturnRequestService $returns,
    ): RedirectResponse {
        $model = $this->orderForCustomer($request, $order);

        $validated = $request->validate([
            'customer_note' => ['nullable', 'string', 'max:1000'],
            'items' => ['required', 'array'],
            'items.*.quantity' => ['nullable', 'numeric', 'min:0'],
            'items.*.reason' => ['nullable', 'string', Rule::in(OnlineOrderReturnRequestItem::REASONS)],
            'items.*.photo' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:'.OnlineOrderReturnRequestService::MAX_PHOTO_KB],
        ]);

        $items = [];

        foreach ($request->input('items', []) as $itemId => $line) {
            $items[$itemId] = [
                'quantity' => $line['quantity'] ?? '0',
                'reason' => $line['reason'] ?? '',
                'photo' => $request->file('items.'.$itemId.'.photo'),
            ];
        }

        try {
            $returns->submit($model, [
                'customer_note' => $validated['customer_note'] ?? '',
                'items' => $items,
            ]);
        } catch (ValidationException $exception) {
            throw $exception;
        }

        return redirect()
            ->route('store.customer.orders.return', $model->order_number)
            ->with('customer_auth_status', __('agricart.customer.returns.submitted'));
    }

    public function cancel(
        Request $request,
        string $order,
        OnlineOrderReturnRequestService $returns,
    ): RedirectResponse {
        $model = $this->orderForCustomer($request, $order);
        $pending = $returns->pendingForOrder($model);

        if ($pending === null) {
            return redirect()->route('store.customer.orders.return', $model->order_number);
        }

        try {
            $returns->cancel($pending);
        } catch (ValidationException $exception) {
            throw $exception;
        }

        return redirect()
            ->route('store.customer.orders.show', $model->order_number)
            ->with('customer_auth_status', __('agricart.customer.returns.cancelled'));
    }

    private function orderForCustomer(Request $request, string $order): OnlineSaleOrder
    {
        $customer = $request->user('customer');

        if (! $customer instanceof Customer) {
            abort(403);
        }

        return OnlineSaleOrder::query()
            ->where('order_number', $order)
            ->where('customer_id', $customer->id)
            ->with(['items.product'])
            ->firstOrFail();
    }
}
