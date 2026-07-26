<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Services\Analytics\AnalyticsContext;
use App\Services\Store\StoreAboutService;
use App\Services\Store\StorefrontSchemaService;
use App\Services\Store\StoreLayoutService;
use Illuminate\Contracts\View\View;

class AboutController extends Controller
{
    public function show(
        StoreLayoutService $layout,
        StoreAboutService $about,
        StorefrontSchemaService $schema,
        AnalyticsContext $analytics,
    ): View {
        $analytics->setPage('about', null, 'about');

        $page = $about->publicPageData();

        return view('store.about.show', [
            ...$layout->layoutData(),
            ...$page,
            'schemaDocument' => $schema->aboutDocument($page),
        ]);
    }
}
