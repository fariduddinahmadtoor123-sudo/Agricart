<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Services\Store\StoreLayoutService;
use Illuminate\Contracts\View\View;

class SupplierRegisterController extends Controller
{
    public function show(StoreLayoutService $layout): View
    {
        return view('store.auth.supplier-register', $layout->layoutData());
    }
}
