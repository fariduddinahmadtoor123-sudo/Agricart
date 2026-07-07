<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PurchasePaymentSummary;
use App\Models\User;
use Illuminate\Contracts\View\View;

class PurchasePaymentSummaryPrintController extends Controller
{
    public function show(PurchasePaymentSummary $summary): View
    {
        $this->authorizePurchasingAccess();

        $summary->load(['items' => fn ($query) => $query->orderBy('sort_order')]);

        $locale = $summary->displayLocale();
        app()->setLocale($locale);

        return view('prints.purchase-payment-summary', [
            'summary' => $summary,
            'locale' => $locale,
            'autoprint' => request()->query('autoprint', '1') !== '0',
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
