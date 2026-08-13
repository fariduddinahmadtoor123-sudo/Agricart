<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CompanyProfile;
use App\Models\OnlineSaleOrder;
use App\Models\PrintProfile;
use App\Models\User;
use App\Services\Printing\PrintProfileService;
use App\Services\Sales\OnlineSaleOrderService;
use Illuminate\Contracts\View\View;

class OnlineSaleOrderPrintController extends Controller
{
    public function show(OnlineSaleOrder $order): View
    {
        $this->authorizeSalesAccess();

        $order->load([
            'items' => fn ($query) => $query->orderBy('sort_order'),
            'customer',
            'sale',
            'assignedUser',
            'supplierAssignments.supplier',
        ]);

        $localeQuery = request()->query('locale');
        $locale = is_string($localeQuery) && in_array($localeQuery, OnlineSaleOrderService::LOCALES, true)
            ? $localeQuery
            : (in_array($order->locale, OnlineSaleOrderService::LOCALES, true) ? $order->locale : 'en');
        app()->setLocale($locale);

        $company = CompanyProfile::current();

        $paper = request()->query('paper', 'a4');
        if (! in_array($paper, ['a4', 'receipt'], true)) {
            $paper = 'a4';
        }

        $receiptProfile = app(PrintProfileService::class)
            ->defaultForCategory(PrintProfile::CATEGORY_RECEIPT);

        $total = (float) $order->total_amount;
        $paid = (float) $order->amount_paid;
        $balanceDue = max(0, $total - $paid);

        return view('prints.online-sale-order', [
            'order' => $order,
            'locale' => $locale,
            'company' => $company,
            'storeName' => (string) ($company->localized('business_name') ?? ''),
            'storePhone' => (string) (($company->primaryPhone() ?? [])['phone_number'] ?? ''),
            'storeAddress' => $company->formattedPrimaryAddress() ?? '',
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
                && ($user->isSuperAdmin() || \App\Services\Access\ModulePageAccess::can($user, 'sales', 'online-orders', 'print')),
            403,
        );
    }
}
