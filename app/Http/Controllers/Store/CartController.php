<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Services\Store\StoreCartService;
use App\Services\Store\StoreLayoutService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class CartController extends Controller
{
    public function index(StoreLayoutService $layout, StoreCartService $cart): View
    {
        return view('store.cart.index', [
            ...$layout->layoutData(),
            'lines' => $cart->lines(),
            'subtotal' => $cart->formattedSubtotal(),
        ]);
    }

    public function add(Request $request, StoreCartService $cart): RedirectResponse
    {
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

    public function update(Request $request, StoreCartService $cart): RedirectResponse
    {
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

    public function remove(int $productId, StoreCartService $cart): RedirectResponse
    {
        $cart->remove($productId);

        return redirect()
            ->route('store.cart')
            ->with('store_status', __('agricart.storefront.cart.removed'));
    }
}
