<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CompanyProfile;
use App\Models\OnlineSaleOrder;
use App\Models\Supplier;
use App\Models\User;
use App\Services\Sales\OnlineSaleOrderService;
use Illuminate\Contracts\View\View;

class OnlineSaleOrderPackingSlipPrintController extends Controller
{
    public function show(OnlineSaleOrder $order): View
    {
        $this->authorizeSalesAccess();

        $order->load([
            'items' => fn ($query) => $query->orderBy('sort_order'),
            'assignedUser',
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

        $company = CompanyProfile::current();

        return view('prints.online-sale-order-packing-slip', [
            'order' => $order,
            'locale' => $locale,
            'company' => $company,
            'storeName' => (string) ($company->localized('business_name') ?? ''),
            'storeLogoUrl' => $company->displayLogoUrl(),
            'autoprint' => request()->query('autoprint', '1') !== '0',
            'recipientName' => $this->resolveRecipientName(),
        ]);
    }

    private function resolveRecipientName(): ?string
    {
        $supplierId = (int) request()->query('supplier_id', 0);

        if ($supplierId > 0) {
            $supplier = Supplier::query()->find($supplierId);

            if ($supplier !== null) {
                $name = trim($supplier->displayBusinessName());

                return $name !== '' ? $name : null;
            }
        }

        $staffId = (int) request()->query('staff_id', 0);

        if ($staffId > 0) {
            $staff = User::query()->find($staffId);
            $name = trim((string) ($staff?->name ?? ''));

            return $name !== '' ? $name : null;
        }

        return null;
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
