<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CatalogControl;
use App\Models\CompanyProfile;
use App\Models\PrintProfile;
use App\Models\Product;
use App\Models\SaleQuotation;
use App\Models\User;
use App\Services\Printing\PrintProfileService;
use App\Services\Sales\SaleQuotationService;
use Illuminate\Contracts\View\View;

class SaleQuotationPrintController extends Controller
{
    public function show(SaleQuotation $quotation): View
    {
        $this->authorizeSalesAccess();

        $quotation->load([
            'items' => fn ($query) => $query->orderBy('sort_order'),
            'customer',
            'preparedBy',
        ]);

        $localeQuery = request()->query('locale');
        $locale = is_string($localeQuery) && in_array($localeQuery, SaleQuotationService::LOCALES, true)
            ? $localeQuery
            : $quotation->displayLocale();
        app()->setLocale($locale);

        $company = CompanyProfile::current();

        $paper = request()->query('paper', 'a4');
        if (! in_array($paper, ['a4', 'receipt'], true)) {
            $paper = 'a4';
        }

        $receiptProfile = app(PrintProfileService::class)
            ->defaultForCategory(PrintProfile::CATEGORY_RECEIPT);

        $controls = [];

        if ($quotation->print_controls) {
            $productIds = $quotation->items->pluck('product_id')->filter()->unique()->values();

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

                        return filled($value) ? trim($name . ': ' . $value) : $name;
                    })->filter()->values()->all();

                    return [$product->id => $lines];
                })
                ->filter(fn (array $list): bool => $list !== [])
                ->all();
        }

        return view('prints.sale-quotation', [
            'quotation' => $quotation,
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
