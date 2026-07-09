<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\OnlineSaleOrder;
use App\Services\Sales\SaleService;
use App\Services\Store\StoreLayoutService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

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

    public function show(Request $request, string $order, StoreLayoutService $layout, SaleService $sales): View
    {
        /** @var Customer $customer */
        $customer = $request->user('customer');

        $model = OnlineSaleOrder::query()
            ->where('order_number', $order)
            ->where('customer_id', $customer->id)
            ->with(['items.product'])
            ->firstOrFail();

        return view('store.orders.show', [
            ...$layout->layoutData(),
            'order' => $model,
            'sales' => $sales,
        ]);
    }
}
