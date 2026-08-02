<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Services\Store\StoreLayoutService;
use App\Services\Store\StoreRegistrationService;
use Illuminate\Contracts\View\View;

class SupplierRegisterController extends Controller
{
    public function show(StoreLayoutService $layout, StoreRegistrationService $registration): View
    {
        return view('store.auth.supplier-register', array_merge(
            $layout->layoutData(),
            ['registrationNotice' => $registration->publicNotice('supplier')],
        ));
    }
}
