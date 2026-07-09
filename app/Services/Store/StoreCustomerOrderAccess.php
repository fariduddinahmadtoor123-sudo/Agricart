<?php

namespace App\Services\Store;

use App\Models\Customer;
use App\Models\OnlineSaleOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StoreCustomerOrderAccess
{
    public function findByOrderNumber(string $orderNumber): ?OnlineSaleOrder
    {
        return OnlineSaleOrder::query()
            ->where('order_number', $orderNumber)
            ->first();
    }

    public function customerMayView(Customer $customer, OnlineSaleOrder $order): bool
    {
        if ($order->customer_id === null) {
            return false;
        }

        return (int) $customer->id === (int) $order->customer_id;
    }

    public function mayViewCheckoutSuccess(Request $request, OnlineSaleOrder $order): bool
    {
        if ($request->session()->get('placed_order_number') === $order->order_number) {
            return true;
        }

        $customer = Auth::guard('customer')->user();

        if (! $customer instanceof Customer) {
            return false;
        }

        return $this->customerMayView($customer, $order);
    }

    /**
     * Resolve an order for the checkout success page or abort with 404 (no enumeration).
     */
    public function resolveCheckoutSuccessOrNotFound(Request $request, string $orderNumber): OnlineSaleOrder
    {
        $order = $this->findByOrderNumber($orderNumber);

        if (! $order instanceof OnlineSaleOrder || ! $this->mayViewCheckoutSuccess($request, $order)) {
            abort(404);
        }

        if ($request->session()->get('placed_order_number') === $orderNumber) {
            $request->session()->forget('placed_order_number');
        }

        return $order;
    }
}
