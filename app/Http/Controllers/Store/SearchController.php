<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Services\Store\StoreLayoutService;
use App\Services\Store\StoreProductService;
use Illuminate\Contracts\View\View;
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
}
