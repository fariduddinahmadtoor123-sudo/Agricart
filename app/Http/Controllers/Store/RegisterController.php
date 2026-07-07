<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Services\Store\StoreLayoutService;
use Illuminate\Contracts\View\View;

class RegisterController extends Controller
{
    public function show(StoreLayoutService $layout): View
    {
        return view('store.auth.register', $layout->layoutData());
    }
}
