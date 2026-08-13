<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CompanyProfile;
use App\Models\PrintProfile;
use App\Models\PurchaseQuotation;
use App\Models\User;
use App\Services\Printing\PrintProfileService;
use Illuminate\Contracts\View\View;

class PurchaseQuotationPrintController extends Controller
{
    public function show(PurchaseQuotation $quotation): View
    {
        $this->authorizePurchasingAccess();

        $quotation->load([
            'items' => fn ($query) => $query->orderBy('sort_order'),
            'supplier',
        ]);

        $locale = $quotation->displayLocale();
        app()->setLocale($locale);

        $company = CompanyProfile::current();
        $buyerName = (string) ($company->localized('business_name') ?? '');
        $buyerAddress = $company->formattedPrimaryAddress() ?? '';
        $buyerPhone = (string) (($company->primaryPhone() ?? [])['phone_number'] ?? '');

        $paper = request()->query('paper', 'a4');
        if (! in_array($paper, ['a4', 'receipt'], true)) {
            $paper = 'a4';
        }

        $receiptProfile = app(PrintProfileService::class)
            ->defaultForCategory(PrintProfile::CATEGORY_RECEIPT);

        return view('prints.purchase-quotation', [
            'quotation' => $quotation,
            'locale' => $locale,
            'buyerName' => $buyerName,
            'buyerAddress' => $buyerAddress,
            'buyerPhone' => $buyerPhone,
            'paper' => $paper,
            'receiptProfile' => $receiptProfile,
            'autoprint' => request()->query('autoprint', '1') !== '0',
        ]);
    }

    private function authorizePurchasingAccess(): void
    {
        $user = auth()->user();

        abort_unless(
            $user instanceof User
                && \App\Services\Access\ModulePageAccess::can($user, 'purchasing', 'purchase-quotations', 'print'),
            403,
        );
    }
}
