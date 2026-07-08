<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Models\StoreSetting;
use App\Services\SalesChannels\SalesChannelService;
use App\Services\Store\StoreLayoutService;
use App\Services\Store\StoreProductService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function index(Request $request, StoreLayoutService $layout, StoreProductService $products): View
    {
        $query = trim((string) $request->query('q', ''));
        $results = filled($query) ? $products->paginatedSearch($query) : null;

        return view('store.search.index', [
            ...$layout->layoutData(),
            'query' => $query,
            'products' => $results,
            'productService' => $products,
        ]);
    }

    public function fragment(Request $request, StoreProductService $products, SalesChannelService $channels): View
    {
        $query = trim((string) $request->query('q', ''));
        $results = filled($query) ? $products->paginatedSearch($query) : null;

        return view('store.components.search-results-panel', [
            'query' => $query,
            'products' => $results,
            'productService' => $products,
            'onlineStoreChannel' => $channels->onlineStoreViewState(),
            'settings' => StoreSetting::current(),
        ]);
    }

    public function suggest(Request $request, StoreProductService $products): JsonResponse
    {
        $query = trim((string) $request->query('q', ''));

        if (mb_strlen($query) < 2) {
            return response()->json([
                'query' => $query,
                'results' => [],
                'search_url' => route('store.search', ['q' => $query]),
            ]);
        }

        $items = $products->searchPreview($query, 25);

        $results = $items->map(function ($product) use ($products): array {
            $brand = $products->localizedBrandName($product);

            return [
                'id' => $product->id,
                'name' => $products->localizedName($product),
                'url' => route('store.product', $product->slug),
                'image' => $products->imageUrl($product),
                'price' => ($amount = $products->formattedRetailPrice($product))
                    ? __('agricart.storefront.products.price', ['amount' => $amount])
                    : null,
                'code' => filled($product->code) ? $product->code : null,
                'brand' => $brand,
            ];
        })->values();

        return response()->json([
            'query' => $query,
            'results' => $results,
            'search_url' => route('store.search', ['q' => $query]),
        ]);
    }
}
