<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Services\SalesChannels\SalesChannelService;
use App\Services\Store\StoreCartService;
use App\Services\Store\StoreLayoutService;
use App\Services\Store\StoreProductService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class CartController extends Controller
{
    public function index(StoreLayoutService $layout, StoreCartService $cart, SalesChannelService $channels): View|RedirectResponse
    {
        if (! $channels->onlineStoreAllowsPurchasing()) {
            return redirect()
                ->route('store.home')
                ->with('store_status', __('agricart.storefront.channel.purchasing_disabled'))
                ->with('store_status_type', 'warning');
        }

        return view('store.cart.index', [
            ...$layout->layoutData(),
            'lines' => $cart->lines(),
            'subtotal' => $cart->formattedSubtotal(),
            'productService' => app(StoreProductService::class),
        ]);
    }

    public function print(StoreLayoutService $layout, StoreCartService $cart, SalesChannelService $channels): View|RedirectResponse
    {
        if (! $channels->onlineStoreAllowsPurchasing()) {
            return redirect()
                ->route('store.home')
                ->with('store_status', __('agricart.storefront.channel.purchasing_disabled'))
                ->with('store_status_type', 'warning');
        }

        if ($cart->isEmpty()) {
            return redirect()
                ->route('store.cart')
                ->with('store_status', __('agricart.storefront.cart.empty'));
        }

        return view('store.cart.print', [
            ...$layout->layoutData(),
            'lines' => $cart->lines(),
            'subtotal' => $cart->formattedSubtotal(),
            'productService' => app(StoreProductService::class),
            'printedAt' => now(),
        ]);
    }

    public function add(Request $request, StoreCartService $cart, SalesChannelService $channels): RedirectResponse
    {
        if (! $channels->onlineStoreAllowsPurchasing()) {
            return redirect()
                ->back()
                ->with('store_status', __('agricart.storefront.channel.purchasing_disabled'))
                ->with('store_status_type', 'warning');
        }

        $validated = $request->validate([
            'product_id' => ['required', 'integer'],
            'quantity' => ['nullable', 'numeric', 'min:1'],
        ]);

        try {
            $cart->add(
                (int) $validated['product_id'],
                (float) ($validated['quantity'] ?? 1),
            );
        } catch (ValidationException $exception) {
            return back()
                ->withInput()
                ->withErrors($exception->errors());
        }

        return redirect()
            ->route('store.cart')
            ->with('store_status', __('agricart.storefront.cart.added'));
    }

    public function update(Request $request, StoreCartService $cart, SalesChannelService $channels): RedirectResponse
    {
        if (! $channels->onlineStoreAllowsPurchasing()) {
            return redirect()
                ->route('store.home')
                ->with('store_status', __('agricart.storefront.channel.purchasing_disabled'))
                ->with('store_status_type', 'warning');
        }

        $validated = $request->validate([
            'product_id' => ['required', 'integer'],
            'quantity' => ['required', 'numeric', 'min:0'],
        ]);

        try {
            $cart->setQuantity((int) $validated['product_id'], (float) $validated['quantity']);
        } catch (ValidationException $exception) {
            return back()->withErrors($exception->errors());
        }

        return redirect()
            ->route('store.cart')
            ->with('store_status', __('agricart.storefront.cart.updated'));
    }

    public function remove(int $productId, StoreCartService $cart, SalesChannelService $channels): RedirectResponse
    {
        if (! $channels->onlineStoreAllowsPurchasing()) {
            return redirect()
                ->route('store.home')
                ->with('store_status', __('agricart.storefront.channel.purchasing_disabled'))
                ->with('store_status_type', 'warning');
        }

        $cart->remove($productId);

        return redirect()
            ->route('store.cart')
            ->with('store_status', __('agricart.storefront.cart.removed'));
    }
}
