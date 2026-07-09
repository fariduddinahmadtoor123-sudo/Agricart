<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Models\OnlineSaleOrder;
use App\Services\Sales\OnlineSaleOrderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CustomerOrderPaymentController extends Controller
{
    public function store(Request $request, string $order, OnlineSaleOrderService $orders): RedirectResponse
    {
        /** @var \App\Models\Customer $customer */
        $customer = $request->user('customer');

        $model = OnlineSaleOrder::query()
            ->where('order_number', $order)
            ->where('customer_id', $customer->id)
            ->firstOrFail();

        $orders->submitCustomerPaymentProof($model, $customer, $request->all());

        return redirect()
            ->route('store.customer.orders.show', $model->order_number)
            ->with('customer_auth_status', __('agricart.customer.orders.payment_proof_submitted'))
            ->with('customer_auth_status_type', 'success');
    }
}
