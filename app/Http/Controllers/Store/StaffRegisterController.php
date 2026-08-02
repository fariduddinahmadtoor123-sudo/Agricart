<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Services\Store\StoreLayoutService;
use App\Services\Store\StoreRegistrationService;
use Illuminate\Contracts\View\View;

class StaffRegisterController extends Controller
{
    public function show(StoreLayoutService $layout, StoreRegistrationService $registration): View
    {
        return view('store.auth.register', array_merge(
            $layout->layoutData(),
            ['registrationNotice' => $registration->publicNotice('staff')],
        ));
    }
}
