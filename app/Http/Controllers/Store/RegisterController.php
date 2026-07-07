<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;

class RegisterController extends Controller
{
    /**
     * Legacy storefront URL — online customers register via customer login.
     */
    public function show(): RedirectResponse
    {
        return redirect()->route('store.customer.login', ['tab' => 'register']);
    }
}
