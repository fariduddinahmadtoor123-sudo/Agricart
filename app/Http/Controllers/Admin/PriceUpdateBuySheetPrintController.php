<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class PriceUpdateBuySheetPrintController extends Controller
{
    public function show(Request $request): View
    {
        $this->authorizePurchasingAccess();

        $payload = Cache::pull('price_update_buy_print:'.(string) $request->query('token', ''));

        abort_if(! is_array($payload), 404);

        $locale = (string) ($payload['locale'] ?? 'en');
        if (! in_array($locale, ['en', 'ur', 'both'], true)) {
            $locale = 'en';
        }

        $rows = is_array($payload['rows'] ?? null) ? $payload['rows'] : [];

        return view('prints.price-update-buy-sheet', [
            'rows' => $rows,
            'locale' => $locale,
            'printedAt' => now(),
            'autoprint' => $request->query('autoprint', '1') !== '0',
        ]);
    }

    private function authorizePurchasingAccess(): void
    {
        $user = auth()->user();

        abort_unless(
            $user instanceof User
                && ($user->isSuperAdmin() || $user->hasPermission('purchasing.view')),
            403,
        );
    }
}
