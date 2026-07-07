<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CompanyProfile;
use App\Models\PrintProfile;
use App\Models\PurchasePaymentSummary;
use App\Models\User;
use App\Services\Printing\PrintProfileService;
use App\Services\Purchasing\PurchasePaymentSummaryService;
use Illuminate\Contracts\View\View;

class PurchasePaymentSummaryPrintController extends Controller
{
    public function show(PurchasePaymentSummary $summary): View
    {
        $this->authorizePurchasingAccess();

        $summary->load(['items' => fn ($query) => $query->orderBy('sort_order'), 'purchaser']);

        $locale = $summary->displayLocale();
        app()->setLocale($locale);

        $company = CompanyProfile::current();
        $storeName = (string) ($company->localized('business_name') ?? '');
        $user = auth()->user();
        $printedByName = $user instanceof User ? (string) $user->name : '';
        $purchaserName = $summary->purchaserName();

        $paperSize = app(PurchasePaymentSummaryService::class)
            ->normalizePaperSize((string) $summary->paper_size);

        $receiptProfile = app(PrintProfileService::class)
            ->defaultForCategory(PrintProfile::CATEGORY_RECEIPT);

        return view('prints.purchase-payment-summary', [
            'summary' => $summary,
            'locale' => $locale,
            'storeName' => $storeName,
            'purchaserName' => $purchaserName !== '—' ? $purchaserName : '',
            'printedByName' => $printedByName,
            'paperSize' => $paperSize,
            'receiptProfile' => $receiptProfile,
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
