<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Models\BankAccount;
use App\Models\Customer;
use App\Models\OnlineSaleOrder;
use App\Models\ProductReview;
use App\Services\Sales\OnlineOrderReturnRequestService;
use App\Services\Sales\SaleService;
use App\Services\Store\StoreLayoutService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class CustomerOrderController extends Controller
{
    public function index(Request $request, StoreLayoutService $layout, SaleService $sales): View
    {
        /** @var Customer $customer */
        $customer = $request->user('customer');

        $orders = $customer->onlineSaleOrders()
            ->withCount('items')
            ->with(['items' => fn ($query) => $query->orderBy('sort_order')->limit(4)])
            ->orderByDesc('order_date')
            ->orderByDesc('id')
            ->paginate(15);

        return view('store.orders.index', [
            ...$layout->layoutData(),
            'orders' => $orders,
            'sales' => $sales,
        ]);
    }

    public function show(
        Request $request,
        string $order,
        StoreLayoutService $layout,
        SaleService $sales,
        OnlineOrderReturnRequestService $returns,
    ): View {
        /** @var Customer $customer */
        $customer = $request->user('customer');

        $model = OnlineSaleOrder::query()
            ->where('order_number', $order)
            ->where('customer_id', $customer->id)
            ->with(['items.product', 'supplierAssignments'])
            ->firstOrFail();

        $productIds = $model->items
            ->pluck('product_id')
            ->filter()
            ->unique()
            ->values();

        /** @var Collection<int, ProductReview> $reviewsByProduct */
        $reviewsByProduct = $productIds->isEmpty()
            ? collect()
            : ProductReview::query()
                ->where('customer_id', $customer->id)
                ->whereIn('product_id', $productIds)
                ->get()
                ->keyBy('product_id');

        $canReturn = $returns->orderIsReturnable($model)
            && $model->items->contains(fn ($item): bool => $returns->remainingQuantity($item) > 0);

        return view('store.orders.show', [
            ...$layout->layoutData(),
            'order' => $model,
            'sales' => $sales,
            'reviewsByProduct' => $reviewsByProduct,
            'canReturn' => $canReturn,
            'pendingReturn' => $returns->pendingForOrder($model),
            'bankTransferAccounts' => BankAccount::query()
                ->where('status', BankAccount::STATUS_ACTIVE)
                ->orderBy('account_code')
                ->get([
                    'id', 'name_en', 'name_ur', 'bank_name_en', 'bank_name_ur',
                    'account_title', 'account_number', 'iban', 'currency',
                ]),
        ]);
    }
}
