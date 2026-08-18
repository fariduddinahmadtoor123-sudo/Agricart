<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Purchasing\ShopRateListService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class ShopRateListPrintController extends Controller
{
    public function show(Request $request, ShopRateListService $rateLists): View
    {
        $this->authorizePurchasingAccess();

        $locale = $rateLists->normalizeLocale((string) $request->query('locale', 'en'));
        $search = trim((string) $request->query('search', ''));

        if (in_array($locale, ['en', 'ur'], true)) {
            app()->setLocale($locale);
        }

        $rows = $rateLists->rowsForPrint($search !== '' ? $search : null, $locale);
        $pages = $rateLists->chunkForPages($rows);
        $meta = $rateLists->printMeta($locale);

        return view('prints.shop-rate-list', [
            'locale' => $locale,
            'search' => $search,
            'rows' => $rows,
            'pages' => $pages,
            'meta' => $meta,
            'bothNames' => $locale === 'both',
            'autoprint' => $request->query('autoprint', '1') !== '0',
        ]);
    }

    private function authorizePurchasingAccess(): void
    {
        $user = auth()->user();

        abort_unless(
            $user instanceof User
                && \App\Services\Access\ModulePageAccess::can($user, 'purchasing', 'rate-list', 'print'),
            403,
        );
    }
}
