<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CompanyProfile;
use App\Models\Supplier;
use App\Models\User;
use App\Services\Contacts\SupplierPrintPresenter;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class SupplierPrintController extends Controller
{
    public function show(Request $request): View
    {
        $this->authorizePrint();

        $localeQuery = $request->query('locale');
        $locale = is_string($localeQuery) && in_array($localeQuery, ['en', 'ur'], true)
            ? $localeQuery
            : (app()->getLocale() === 'ur' ? 'ur' : 'en');
        app()->setLocale($locale);

        $ids = $this->parseIds($request->query('ids'));
        $query = Supplier::query()->with('categories');

        if ($ids !== []) {
            $query->whereIn('id', $ids);
        } else {
            $this->applyDirectoryFilters($query, $request);
            $query->orderBy('business_name_en')->orderBy('id');
        }

        $suppliers = $query->limit(500)->get();

        if ($ids !== []) {
            $order = array_flip($ids);
            $suppliers = $suppliers
                ->sortBy(fn (Supplier $supplier): int => $order[$supplier->id] ?? 1000)
                ->values();
        }
        $cards = app(SupplierPrintPresenter::class)->cards($suppliers);
        $company = CompanyProfile::current();
        $single = count($cards) === 1;

        return view('prints.suppliers', [
            'cards' => $cards,
            'single' => $single,
            'locale' => $locale,
            'company' => $company,
            'storeName' => (string) ($company->localized('business_name') ?? ''),
            'storePhone' => (string) (($company->primaryPhone() ?? [])['phone_number'] ?? ''),
            'autoprint' => $request->query('autoprint', '1') !== '0',
            'printUrlEn' => $this->printUrl($request, 'en'),
            'printUrlUr' => $this->printUrl($request, 'ur'),
        ]);
    }

    private function authorizePrint(): void
    {
        $user = auth()->user();

        abort_unless(
            $user instanceof User
                && ($user->isSuperAdmin()
                    || \App\Services\Access\ModulePageAccess::can($user, 'contacts', 'suppliers', 'print')
                    || \App\Services\Access\ModulePageAccess::can($user, 'contacts', 'suppliers', 'view')),
            403,
        );
    }

    /**
     * @return list<int>
     */
    private function parseIds(mixed $raw): array
    {
        if (! is_string($raw) || trim($raw) === '') {
            return [];
        }

        $ids = [];

        foreach (explode(',', $raw) as $part) {
            $id = (int) trim($part);

            if ($id > 0) {
                $ids[] = $id;
            }
        }

        return array_values(array_unique($ids));
    }

    /**
     * @param  Builder<Supplier>  $query
     */
    private function applyDirectoryFilters(Builder $query, Request $request): void
    {
        $search = trim((string) $request->query('q', ''));
        $status = trim((string) $request->query('status', ''));
        $city = trim((string) $request->query('city', ''));

        $query->when(filled($search), function (Builder $inner) use ($search): void {
            $needle = '%'.$search.'%';
            $digits = preg_replace('/\D+/', '', $search) ?? '';

            $inner->where(function (Builder $where) use ($needle, $digits): void {
                $where->where('supplier_code', 'like', $needle)
                    ->orWhere('business_name_en', 'like', $needle)
                    ->orWhere('business_name_ur', 'like', $needle)
                    ->orWhere('city_en', 'like', $needle)
                    ->orWhere('city_ur', 'like', $needle)
                    ->orWhere('email', 'like', $needle)
                    ->orWhere('contacts', 'like', $needle)
                    ->orWhere('custom_categories', 'like', $needle)
                    ->orWhereHas('categories', function (Builder $categories) use ($needle): void {
                        $categories->where(function (Builder $categoryQuery) use ($needle): void {
                            $categoryQuery->where('name_en', 'like', $needle)
                                ->orWhere('name_ur', 'like', $needle)
                                ->orWhere('code', 'like', $needle)
                                ->orWhere('slug', 'like', $needle);
                        });
                    });

                if ($digits !== '') {
                    $where->orWhere('contacts', 'like', '%'.$digits.'%');
                }
            });
        });

        if (filled($status) && in_array($status, [Supplier::STATUS_ACTIVE, Supplier::STATUS_INACTIVE], true)) {
            $query->where('status', $status);
        } else {
            $query->whereIn('status', [Supplier::STATUS_ACTIVE, Supplier::STATUS_INACTIVE]);
        }

        $query->when(filled($city), fn (Builder $inner) => $inner->where('city_key', $city));
    }

    private function printUrl(Request $request, string $locale): string
    {
        return route('admin.suppliers.print', array_filter([
            'ids' => $request->query('ids'),
            'q' => $request->query('q'),
            'status' => $request->query('status'),
            'city' => $request->query('city'),
            'locale' => $locale,
            'autoprint' => '0',
        ], fn (mixed $value): bool => $value !== null && $value !== ''));
    }
}
