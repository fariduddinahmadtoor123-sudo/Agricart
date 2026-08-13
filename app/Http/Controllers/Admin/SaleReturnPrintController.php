<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CompanyProfile;
use App\Models\PrintProfile;
use App\Models\SaleReturn;
use App\Models\User;
use App\Services\Printing\PrintProfileService;
use Illuminate\Contracts\View\View;

class SaleReturnPrintController extends Controller
{
    public function show(SaleReturn $saleReturn): View
    {
        $this->authorizeSalesAccess();

        $saleReturn->load([
            'items' => fn ($query) => $query->orderBy('sort_order'),
            'customer',
            'sale',
            'processedBy',
        ]);

        app()->setLocale(app()->getLocale());

        $company = CompanyProfile::current();

        $paper = request()->query('paper', 'a4');
        if (! in_array($paper, ['a4', 'receipt'], true)) {
            $paper = 'a4';
        }

        $receiptProfile = app(PrintProfileService::class)
            ->defaultForCategory(PrintProfile::CATEGORY_RECEIPT);

        $total = (float) $saleReturn->refund_total;
        $paid = (float) $saleReturn->amount_paid;
        $balanceDue = max(0, $total - $paid);

        return view('prints.sale-return', [
            'return' => $saleReturn,
            'company' => $company,
            'storeName' => (string) ($company->localized('business_name') ?? ''),
            'storePhone' => (string) (($company->primaryPhone() ?? [])['phone_number'] ?? ''),
            'storeMessage' => (string) ($company->localized('tagline') ?? ''),
            'storeLogoUrl' => $company->displayLogoUrl(),
            'receiptProfile' => $receiptProfile,
            'paper' => $paper,
            'balanceDue' => $balanceDue,
            'autoprint' => request()->query('autoprint', '1') !== '0',
        ]);
    }

    private function authorizeSalesAccess(): void
    {
        $user = auth()->user();

        abort_unless(
            $user instanceof User
                && ($user->isSuperAdmin() || \App\Services\Access\ModulePageAccess::can($user, 'sales', 'sales-returns', 'print')),
            403,
        );
    }
}
