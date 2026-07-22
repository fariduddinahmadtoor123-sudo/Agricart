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

class OnlineSaleOrderPackingSlipPrintController extends Controller
{
    public function show(OnlineSaleOrder $order): View
    {
        $this->authorizeSalesAccess();

        $order->load([
            'items' => fn ($query) => $query->orderBy('sort_order'),
        ]);

        $itemIds = $this->parseItemIds(request()->query('item_ids'));

        if ($itemIds !== []) {
            $order->setRelation(
                'items',
                $order->items->whereIn('id', $itemIds)->values(),
            );
        }

        $localeQuery = request()->query('locale');
        $locale = is_string($localeQuery) && in_array($localeQuery, OnlineSaleOrderService::LOCALES, true)
            ? $localeQuery
            : (in_array($order->locale, OnlineSaleOrderService::LOCALES, true) ? $order->locale : 'en');
        app()->setLocale($locale);

        $paper = request()->query('paper', 'a4');
        if (! in_array($paper, ['a4', 'receipt'], true)) {
            $paper = 'a4';
        }

        $company = CompanyProfile::current();
        $receiptProfile = app(PrintProfileService::class)
            ->defaultForCategory(PrintProfile::CATEGORY_RECEIPT);

        return view('prints.online-sale-order-packing-slip', [
            'order' => $order,
            'locale' => $locale,
            'paper' => $paper,
            'company' => $company,
            'storeName' => (string) ($company->localized('business_name') ?? ''),
            'storeLogoUrl' => $company->displayLogoUrl(),
            'receiptProfile' => $receiptProfile,
            'autoprint' => request()->query('autoprint', '1') !== '0',
        ]);
    }

    /**
     * @return list<int>
     */
    private function parseItemIds(mixed $raw): array
    {
        if (is_array($raw)) {
            return array_values(array_unique(array_filter(array_map('intval', $raw))));
        }

        if (! is_string($raw) || trim($raw) === '') {
            return [];
        }

        return array_values(array_unique(array_filter(array_map(
            'intval',
            preg_split('/[,\s]+/', $raw) ?: [],
        ))));
    }

    private function authorizeSalesAccess(): void
    {
        $user = auth()->user();

        abort_unless(
            $user instanceof User
                && ($user->isSuperAdmin() || $user->hasPermission('sales.view')),
            403,
        );
    }
}
