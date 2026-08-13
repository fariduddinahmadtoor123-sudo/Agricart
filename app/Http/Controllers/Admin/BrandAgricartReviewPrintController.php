<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\CompanyProfile;
use App\Models\StoreSetting;
use App\Models\User;
use App\Support\Catalog\BrandAgricartReviewCriteria;
use App\Support\Catalog\BrandAgricartReviewNotice;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Storage;

class BrandAgricartReviewPrintController extends Controller
{
    public function show(Brand $brand): View
    {
        $this->authorizeCatalogAccess();

        $localeQuery = request()->query('locale');
        $locale = is_string($localeQuery) && in_array($localeQuery, ['en', 'ur'], true)
            ? $localeQuery
            : 'en';
        app()->setLocale($locale);

        $company = CompanyProfile::current();
        $criteria = BrandAgricartReviewCriteria::normalizeDetailed($brand->agricart_rating_criteria);
        $settings = StoreSetting::current();

        $brandName = $locale === 'ur' && filled($brand->name_ur)
            ? (string) $brand->name_ur
            : (string) $brand->name_en;

        $description = $locale === 'ur' && filled($brand->description_ur)
            ? (string) $brand->description_ur
            : (string) ($brand->description_en ?? '');

        if ($description === '' && filled($brand->description_en)) {
            $description = (string) $brand->description_en;
        }

        $fullReview = $locale === 'ur' && filled($brand->agricart_rating_reason_ur)
            ? (string) $brand->agricart_rating_reason_ur
            : (string) ($brand->agricart_rating_reason_en ?? '');

        if ($fullReview === '' && filled($brand->agricart_rating_reason_en)) {
            $fullReview = (string) $brand->agricart_rating_reason_en;
        }

        $revision = $locale === 'ur' && filled($brand->agricart_rating_revision_ur)
            ? (string) $brand->agricart_rating_revision_ur
            : (string) ($brand->agricart_rating_revision_en ?? '');

        if ($revision === '' && filled($brand->agricart_rating_revision_en)) {
            $revision = (string) $brand->agricart_rating_revision_en;
        }

        $notice = $locale === 'ur' && filled($settings->brand_review_notice_ur)
            ? (string) $settings->brand_review_notice_ur
            : (string) ($settings->brand_review_notice_en ?? '');

        if ($notice === '') {
            $notice = $locale === 'ur'
                ? BrandAgricartReviewNotice::defaultUrdu()
                : BrandAgricartReviewNotice::defaultEnglish();
        }

        $logoUrl = filled($brand->image_path)
            ? Storage::disk('public')->url($brand->image_path)
            : null;

        $printUrl = static fn (string $forLocale): string => route('admin.brands.print', [
            'brand' => $brand,
            'locale' => $forLocale,
            'autoprint' => '0',
        ]);

        return view('prints.brand-agricart-review', [
            'brand' => $brand,
            'locale' => $locale,
            'company' => $company,
            'storeName' => (string) ($company->localized('business_name') ?? ''),
            'storePhone' => (string) (($company->primaryPhone() ?? [])['phone_number'] ?? ''),
            'brandName' => $brandName,
            'description' => $description,
            'logoUrl' => $logoUrl,
            'criteria' => $criteria,
            'reviewComplete' => BrandAgricartReviewCriteria::isComplete($criteria),
            'score' => BrandAgricartReviewCriteria::score($criteria),
            'totalChecks' => BrandAgricartReviewCriteria::scoreMax(),
            'fullReview' => $fullReview,
            'revision' => $revision,
            'notice' => $notice,
            'printUrlEn' => $printUrl('en'),
            'printUrlUr' => $printUrl('ur'),
            'autoprint' => request()->query('autoprint', '0') === '1',
        ]);
    }

    private function authorizeCatalogAccess(): void
    {
        $user = auth()->user();

        abort_unless(
            $user instanceof User
                && ($user->isSuperAdmin() || \App\Services\Access\ModulePageAccess::can($user, 'catalog', 'brands', 'print') || \App\Services\Access\ModulePageAccess::can($user, 'catalog', 'brands', 'view')),
            403,
        );
    }
}
