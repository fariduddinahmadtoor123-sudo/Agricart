<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Models\StoreSetting;
use App\Services\SalesChannels\SalesChannelService;
use App\Services\Store\StoreLayoutService;
use App\Services\Store\StoreProductService;
use App\Services\Store\StorefrontSearchService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function index(
        Request $request,
        StoreLayoutService $layout,
        StoreProductService $products,
        StorefrontSearchService $search,
    ): View
    {
        $query = $this->query($request);
        $results = filled($query) ? $search->search($query) : null;

        return view('store.search.index', [
            ...$layout->layoutData(),
            'query' => $query,
            'products' => $results['products'] ?? null,
            'categories' => $results['categories'] ?? collect(),
            'pages' => $results['pages'] ?? collect(),
            'productService' => $products,
        ]);
    }

    public function fragment(
        Request $request,
        StoreProductService $products,
        StorefrontSearchService $search,
        SalesChannelService $channels,
    ): View
    {
        $query = $this->query($request);
        $results = filled($query) ? $search->search($query) : null;

        return view('store.components.search-results-panel', [
            'query' => $query,
            'products' => $results['products'] ?? null,
            'categories' => $results['categories'] ?? collect(),
            'pages' => $results['pages'] ?? collect(),
            'productService' => $products,
            'onlineStoreChannel' => $channels->onlineStoreViewState(),
            'settings' => StoreSetting::current(),
        ]);
    }

    public function suggest(Request $request, StorefrontSearchService $search): JsonResponse
    {
        $query = $this->query($request);

        if (mb_strlen($query) < 2) {
            return response()->json([
                'query' => $query,
                'results' => [],
                'search_url' => route('store.search', ['q' => $query]),
            ]);
        }

        $results = $search->preview($query, 25);

        return response()->json([
            'query' => $query,
            'results' => $results,
            'search_url' => route('store.search', ['q' => $query]),
        ]);
    }

    private function query(Request $request): string
    {
        return mb_substr(trim((string) $request->query('q', '')), 0, 120);
    }
}
