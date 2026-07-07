<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CompanyProfile;
use App\Models\PrintProfile;
use App\Models\Purchase;
use App\Models\User;
use App\Services\Printing\PrintProfileService;
use Illuminate\Contracts\View\View;

class PurchasePrintController extends Controller
{
    public function show(Purchase $purchase): View
    {
        $this->authorizePurchasingAccess();

        $purchase->load([
            'items' => fn ($query) => $query->orderBy('sort_order'),
            'supplier',
            'purchaser',
        ]);

        $locale = $purchase->displayLocale();
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

        return view('prints.purchase', [
            'purchase' => $purchase,
            'locale' => $locale,
            'company' => $company,
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
                && ($user->isSuperAdmin() || $user->hasPermission('purchasing.view')),
            403,
        );
    }
}
