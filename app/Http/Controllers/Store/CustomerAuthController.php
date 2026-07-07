<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Services\Store\StoreLayoutService;
use Illuminate\Contracts\View\View;

class CustomerAuthController extends Controller
{
    public function show(StoreLayoutService $layout): View
    {
        return view('store.auth.login', $layout->layoutData());
    }
}
