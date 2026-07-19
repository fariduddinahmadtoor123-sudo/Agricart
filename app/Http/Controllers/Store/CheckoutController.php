<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Models\BankAccount;
use App\Models\Customer;
use App\Models\OnlineSaleOrder;
use App\Services\Sales\OnlineSaleOrderService;
use App\Services\Sales\SaleService;
use App\Services\SalesChannels\SalesChannelService;
use App\Services\Store\CustomerService;
use App\Services\Store\StoreCartService;
use App\Services\Store\StoreCustomerOrderAccess;
use App\Services\Store\StoreLayoutService;
use App\Services\Store\StoreProductService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CheckoutController extends Controller
{
    public function show(StoreLayoutService $layout, StoreCartService $cart, SalesChannelService $channels, StoreProductService $productService): View|RedirectResponse
    {
        if (! $channels->onlineStoreAllowsPurchasing()) {
            return redirect()
                ->route('store.home')
                ->with('store_status', __('agricart.storefront.channel.purchasing_disabled'))
                ->with('store_status_type', 'warning');
        }

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
            'productService' => $productService,
            'paymentMethods' => collect(SaleService::PAYMENT_METHODS)
                ->mapWithKeys(fn (string $method): array => [$method => __('pos.payment_methods.' . $method)])
                ->all(),
            'bankTransferAccounts' => BankAccount::query()
                ->where('status', BankAccount::STATUS_ACTIVE)
                ->orderBy('account_code')
                ->get([
                    'id', 'name_en', 'name_ur', 'bank_name_en', 'bank_name_ur',
                    'account_title', 'account_number', 'iban', 'currency',
                ]),
            'provinces' => config('pakistani_provinces', []),
            'checkoutToken' => (string) Str::uuid(),
        ]);
    }

    public function place(Request $request, StoreLayoutService $layout, StoreCartService $cart, OnlineSaleOrderService $orders, SalesChannelService $channels, CustomerService $customers): RedirectResponse
    {
        if (! $channels->onlineStoreAllowsPurchasing()) {
            return redirect()
                ->route('store.home')
                ->with('store_status', __('agricart.storefront.channel.purchasing_disabled'))
                ->with('store_status_type', 'warning');
        }

        if ($cart->isEmpty()) {
            return redirect()->route('store.cart');
        }

        if (! Auth::guard('customer')->check()) {
            return redirect()->guest(route('store.customer.login', ['redirect' => route('store.checkout')]));
        }

        /** @var Customer $customer */
        $customer = Auth::guard('customer')->user();

        $validated = $request->validate([
            'checkout_token' => ['required', 'string', 'max:64'],
            'customer_payment_method' => ['required', 'string', Rule::in(SaleService::PAYMENT_METHODS)],
            'customer_payment_note' => ['nullable', 'string', 'max:2000'],
            'customer_payment_reference' => ['nullable', 'string', 'max:128'],
            'customer_notes' => ['nullable', 'string', 'max:5000'],
            'bank_receipt' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:5120'],
        ]);

        $shipping = $customers->validateCheckoutShipping($request->all());

        if ($request->boolean('save_address_to_profile')) {
            $customers->syncProfileAddressFromCheckout($customer, $shipping['address']);
        }

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
                'checkout_token' => $validated['checkout_token'],
                'locale' => app()->getLocale(),
                'customer_payment_method' => $validated['customer_payment_method'],
                'customer_payment_note' => $validated['customer_payment_note'] ?? null,
                'customer_payment_reference' => $validated['customer_payment_reference'] ?? null,
                'customer_notes' => $validated['customer_notes'] ?? null,
                'ship_contact_name' => $shipping['ship_contact_name'],
                'ship_phone' => $shipping['ship_phone'],
                'ship_address' => $shipping['ship_address'],
                'ship_city' => $shipping['ship_city'],
                'rows' => $cart->checkoutRows(),
            ]);

            if ($validated['customer_payment_method'] === 'bank_transfer' && $request->hasFile('bank_receipt')) {
                $path = $request->file('bank_receipt')->store(
                    'online-orders/customer-receipts/' . $order->id,
                    'local',
                );
                $order->update([
                    'customer_bank_receipt_path' => $path,
                    'customer_payment_proof_status' => OnlineSaleOrder::PROOF_SUBMITTED,
                ]);
            }

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

    public function success(Request $request, string $order, StoreLayoutService $layout, StoreCustomerOrderAccess $orderAccess): View
    {
        $model = $orderAccess->resolveCheckoutSuccessOrNotFound($request, $order);

        return view('store.checkout.success', [
            ...$layout->layoutData(),
            'orderNumber' => $model->order_number,
            'order' => $model,
        ]);
    }
}
