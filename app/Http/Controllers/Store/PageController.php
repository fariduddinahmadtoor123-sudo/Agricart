<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Models\StorePage;
use App\Services\Analytics\AnalyticsContext;
use App\Services\Store\StoreLayoutService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class PageController extends Controller
{
    public function show(string $slug, StoreLayoutService $layout, AnalyticsContext $analytics): View|RedirectResponse
    {
        if ($slug === 'about-us') {
            return redirect()->route('store.about', status: 301);
        }

        $page = StorePage::query()
            ->where('slug', $slug)
            ->where('is_active', true)
            ->firstOrFail();

        $analytics->setPage('page', $page->id, $page->slug);

        return view('store.page.show', [
            ...$layout->layoutData(),
            'page' => $page,
        ]);
    }
}
