<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PurchasePlanningSheet;
use App\Models\User;
use Illuminate\Contracts\View\View;

class PurchasePlanningPrintController extends Controller
{
    public function show(PurchasePlanningSheet $sheet): View
    {
        $this->authorizePurchasingAccess();

        $sheet->load(['items' => fn ($query) => $query->orderBy('sort_order')]);

        $locale = $sheet->displayLocale();
        app()->setLocale($locale);

        return view('prints.purchase-planning', [
            'sheet' => $sheet,
            'locale' => $locale,
            'autoprint' => request()->query('autoprint', '1') !== '0',
        ]);
    }

    private function authorizePurchasingAccess(): void
    {
        $user = auth()->user();

        abort_unless(
            $user instanceof User
                && \App\Services\Access\ModulePageAccess::can($user, 'purchasing', 'purchase-planning', 'print'),
            403,
        );
    }
}
