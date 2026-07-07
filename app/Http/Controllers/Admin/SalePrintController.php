<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CatalogControl;
use App\Models\CompanyProfile;
use App\Models\PrintProfile;
use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use App\Services\Printing\PrintProfileService;
use App\Services\Sales\SaleService;
use Illuminate\Contracts\View\View;

class SalePrintController extends Controller
{
    public function show(Sale $sale): View
    {
        $this->authorizeSalesAccess();

        $sale->load([
            'items' => fn ($query) => $query->orderBy('sort_order'),
            'customer',
            'cashier',
        ]);

        $localeQuery = request()->query('locale');
        $locale = is_string($localeQuery) && in_array($localeQuery, SaleService::LOCALES, true)
            ? $localeQuery
            : $sale->displayLocale();
        app()->setLocale($locale);

        $company = CompanyProfile::current();

        $paper = request()->query('paper', 'a4');
        if (! in_array($paper, ['a4', 'receipt'], true)) {
            $paper = 'a4';
        }

        $receiptProfile = app(PrintProfileService::class)
            ->defaultForCategory(PrintProfile::CATEGORY_RECEIPT);

        $controls = [];

        if ($sale->print_controls) {
            $productIds = $sale->items->pluck('product_id')->filter()->unique()->values();

            $controls = Product::query()
                ->whereIn('id', $productIds)
                ->with(['controls' => fn ($q) => $q->orderBy('name_en')])
                ->get()
                ->mapWithKeys(function ($product) use ($locale): array {
                    $lines = $product->controls->map(function (CatalogControl $control) use ($locale): string {
                        $name = $locale === 'ur' && filled($control->name_ur)
                            ? (string) $control->name_ur
                            : (string) $control->name_en;
                        $value = $locale === 'ur' && filled($control->value_ur)
                            ? (string) $control->value_ur
                            : (string) ($control->value_en ?? '');

                        if (filled($value)) {
                            return trim($name . ': ' . $value);
                        }

                        return $name;
                    })->filter()->values()->all();

                    return [$product->id => $lines];
                })
                ->filter(fn (array $list): bool => $list !== [])
                ->all();
        }

        $saleService = app(SaleService::class);
        $total = (float) $sale->total_amount;
        $paid = (float) $sale->amount_paid;
        $balanceDue = max(0, $total - $paid);

        return view('prints.sale', [
            'sale' => $sale,
            'locale' => $locale,
            'company' => $company,
            'storeName' => (string) ($company->localized('business_name') ?? ''),
            'storePhone' => (string) (($company->primaryPhone() ?? [])['phone_number'] ?? ''),
            'storeAddress' => $company->formattedPrimaryAddress() ?? '',
            'storeMessage' => (string) ($company->localized('tagline') ?? ''),
            'storeLogoUrl' => $company->displayLogoUrl(),
            'receiptProfile' => $receiptProfile,
            'controlsByProduct' => $controls,
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
                && ($user->isSuperAdmin() || $user->hasPermission('sales.view')),
            403,
        );
    }
}
