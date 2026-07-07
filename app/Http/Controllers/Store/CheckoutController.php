<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Services\Sales\OnlineSaleOrderService;
use App\Services\Sales\SaleService;
use App\Services\Store\StoreCartService;
use App\Services\Store\StoreLayoutService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CheckoutController extends Controller
{
    public function show(StoreLayoutService $layout, StoreCartService $cart): View|RedirectResponse
    {
        if ($cart->isEmpty()) {
            return redirect()->route('store.cart')->with('store_status', __('agricart.storefront.checkout.cart_empty'));
        }

        if (! Auth::guard('customer')->check()) {
            return redirect()
                ->guest(route('store.customer.login', ['redirect' => route('store.checkout')]))
                ->with('store_status', __('agricart.storefront.checkout.login_required'));
        }

        /** @var Customer $customer */
        $customer = Auth::guard('customer')->user();

        return view('store.checkout.show', [
            ...$layout->layoutData(),
            'lines' => $cart->lines(),
            'subtotal' => $cart->formattedSubtotal(),
            'customer' => $customer,
            'paymentMethods' => collect(SaleService::PAYMENT_METHODS)
                ->mapWithKeys(fn (string $method): array => [$method => __('pos.payment_methods.' . $method)])
                ->all(),
        ]);
    }

    public function place(Request $request, StoreLayoutService $layout, StoreCartService $cart, OnlineSaleOrderService $orders): RedirectResponse
    {
        if ($cart->isEmpty()) {
            return redirect()->route('store.cart');
        }

        if (! Auth::guard('customer')->check()) {
            return redirect()->guest(route('store.customer.login', ['redirect' => route('store.checkout')]));
        }

        /** @var Customer $customer */
        $customer = Auth::guard('customer')->user();

        $validated = $request->validate([
            'customer_payment_method' => ['required', 'string', Rule::in(SaleService::PAYMENT_METHODS)],
            'customer_payment_note' => ['nullable', 'string', 'max:2000'],
            'customer_notes' => ['nullable', 'string', 'max:5000'],
        ]);

        try {
            $saleService = app(SaleService::class);

            if (! $saleService->allowsOverselling()) {
                foreach ($cart->lines() as $line) {
                    $stock = $line['product']->stock_qty;

                    if ($stock !== null && $line['quantity_numeric'] > (float) $stock) {
                        throw ValidationException::withMessages([
                            'cart' => [__('agricart.storefront.cart.insufficient_stock', [
                                'available' => $saleService->formatQty((float) $stock),
                            ])],
                        ]);
                    }
                }
            }

            $order = $orders->createFromCheckout([
                'customer_id' => $customer->id,
                'locale' => app()->getLocale(),
                'customer_payment_method' => $validated['customer_payment_method'],
                'customer_payment_note' => $validated['customer_payment_note'] ?? null,
                'customer_notes' => $validated['customer_notes'] ?? null,
                'ship_contact_name' => $customer->displayLabel(),
                'ship_phone' => $customer->primaryPhone(),
                'ship_address' => trim(collect([
                    $customer->address_line1,
                    $customer->address_line2,
                    app()->getLocale() === 'ur' && filled($customer->address_ur) ? $customer->address_ur : $customer->address_en,
                ])->filter()->implode(', ')) ?: null,
                'ship_city' => $customer->city ?? $customer->city_en,
                'rows' => $cart->checkoutRows(),
            ]);

            $cart->clear();

            return redirect()
                ->route('store.checkout.success', ['order' => $order->order_number])
                ->with('placed_order_number', $order->order_number);
        } catch (ValidationException $exception) {
            return back()
                ->withInput()
                ->withErrors($exception->errors());
        }
    }

    public function success(string $order, StoreLayoutService $layout): View
    {
        return view('store.checkout.success', [
            ...$layout->layoutData(),
            'orderNumber' => $order,
        ]);
    }
}
