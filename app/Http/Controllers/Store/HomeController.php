<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Services\Store\StoreCategoryService;
use App\Services\Store\StoreLayoutService;
use Illuminate\Contracts\View\View;

class HomeController extends Controller
{
    public function index(StoreLayoutService $layout, StoreCategoryService $categories): View
    {
        $layoutData = $layout->layoutData();
        $settings = $layoutData['settings'];

        $rootCategories = $settings->category_grid_enabled
            ? $categories->rootCategories()
            : collect();

        return view('store.home.index', [
            ...$layoutData,
            'rootCategories' => $rootCategories,
            'categoryService' => $categories,
        ]);
    }
}
