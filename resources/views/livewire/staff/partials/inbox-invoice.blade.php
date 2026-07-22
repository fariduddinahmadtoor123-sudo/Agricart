@php
    use App\Models\CompanyProfile;
    use App\Services\PricingControls\ChannelPricingEngine;
    use App\Services\Sales\SaleService;

    $saleService = app(SaleService::class);
    $priceFormat = app(ChannelPricingEngine::class);
    $formatAmount = function (string $amount) use ($saleService, $priceFormat): string {
        return $priceFormat->formatPrice($saleService->numeric($amount));
    };

    $company = CompanyProfile::current();
    $locale = app()->getLocale() === 'ur' ? 'ur' : 'en';
    $storeName = (string) ($company->localized('business_name') ?? '');
    $storePhone = (string) (($company->primaryPhone() ?? [])['phone_number'] ?? '');
    $storeAddress = $company->formattedPrimaryAddress() ?? '';
    $storeMessage = (string) ($company->localized('tagline') ?? '');
    $storeLogoUrl = $company->displayLogoUrl();
    $balanceDue = max(0, (float) $sale->total_amount - (float) $sale->amount_paid);
@endphp

<section class="agricart-inbox-invoice" aria-label="{{ __('pos.print.heading') }}">
    <div class="agricart-inbox-invoice__sheet">
        @include('prints.partials.sale-invoice-article', [
            'sale' => $sale,
            'locale' => $locale,
            'storeName' => $storeName,
            'storePhone' => $storePhone,
            'storeAddress' => $storeAddress,
            'storeMessage' => $storeMessage,
            'storeLogoUrl' => $storeLogoUrl,
            'balanceDue' => $balanceDue,
            'controlsByProduct' => [],
            'formatAmount' => $formatAmount,
            'isReceipt' => false,
        ])
    </div>
</section>
