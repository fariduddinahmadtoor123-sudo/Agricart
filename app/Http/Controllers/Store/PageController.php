<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Models\StorePage;
use App\Services\Store\StoreLayoutService;
use Illuminate\Contracts\View\View;

class PageController extends Controller
{
    public function show(string $slug, StoreLayoutService $layout): View
    {
        $page = StorePage::query()
            ->where('slug', $slug)
            ->where('is_active', true)
            ->firstOrFail();

        return view('store.page.show', [
            ...$layout->layoutData(),
            'page' => $page,
        ]);
    }
}
