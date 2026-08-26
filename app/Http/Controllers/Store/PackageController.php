<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Services\Analytics\AnalyticsContext;
use App\Services\SalesChannels\SalesChannelService;
use App\Services\Store\StoreCartService;
use App\Services\Store\StoreLayoutService;
use App\Services\Store\StorePackageService;
use App\Services\Store\StoreProductService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class PackageController extends Controller
{
    public function show(
        string $slug,
        StoreLayoutService $layout,
        StorePackageService $packages,
        StoreProductService $products,
        AnalyticsContext $analytics,
    ): View {
        $package = $packages->findStorefrontBySlug($slug);
        abort_if($package === null, 404);

        $analytics->setPage('package', $package->id, $package->slug);

        $bill = $packages->buildBill($package, 1);

        return view('store.package.show', [
            ...$layout->layoutData(),
            'package' => $package,
            'packageService' => $packages,
            'productService' => $products,
            'bill' => $bill,
        ]);
    }

    public function addToCart(
        Request $request,
        string $slug,
        StorePackageService $packages,
        StoreCartService $cart,
        SalesChannelService $channels,
    ): RedirectResponse {
        if (! $channels->onlineStoreAllowsPurchasing()) {
            return redirect()
                ->back()
                ->with('store_status', __('agricart.storefront.channel.purchasing_disabled'))
                ->with('store_status_type', 'warning');
        }

        $package = $packages->findStorefrontBySlug($slug);
        abort_if($package === null, 404);

        $validated = $request->validate([
            'package_qty' => ['nullable', 'numeric', 'min:1'],
            'quantities' => ['nullable', 'array'],
            'quantities.*' => ['nullable', 'numeric', 'min:0'],
        ]);

        $packageQty = (float) ($validated['package_qty'] ?? 1);
        $overrides = [];

        foreach (($validated['quantities'] ?? []) as $productId => $qty) {
            $overrides[(int) $productId] = (float) $qty;
        }

        $bill = $packages->buildBill($package, $packageQty, $overrides);

        if ($bill['lines'] === []) {
            return back()
                ->with('store_status', __('agricart.storefront.packages.empty_bill'))
                ->with('store_status_type', 'warning');
        }

        $discountFactor = 1 - (max(0, min(100, (float) $package->discount_percent)) / 100);
        $lines = [];

        foreach ($bill['lines'] as $line) {
            $unit = round((float) $line['unit_price'] * $discountFactor, 2);
            $lines[] = [
                'product_id' => $line['product']->id,
                'quantity' => $line['quantity'],
                'unit_price' => $unit,
            ];
        }

        try {
            $cart->addLines($lines);
        } catch (ValidationException $exception) {
            return back()
                ->withInput()
                ->withErrors($exception->errors());
        }

        return redirect()
            ->route('store.cart')
            ->with('store_status', __('agricart.storefront.packages.added'));
    }
}
