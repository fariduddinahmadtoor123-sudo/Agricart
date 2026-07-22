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
    $locale = in_array($order->locale, ['en', 'ur'], true) ? $order->locale : (app()->getLocale() === 'ur' ? 'ur' : 'en');
    $storeName = (string) ($company->localized('business_name') ?? '');
    $storePhone = (string) (($company->primaryPhone() ?? [])['phone_number'] ?? '');
    $storeAddress = $company->formattedPrimaryAddress() ?? '';
    $storeMessage = (string) ($company->localized('tagline') ?? '');
    $storeLogoUrl = $company->displayLogoUrl();
    $balanceDue = max(0, (float) $order->total_amount - (float) $order->amount_paid);
    $customerName = $order->customer?->displayLabel() ?: $order->ship_contact_name;
    $customerPhone = filled($order->ship_phone) ? $order->ship_phone : $order->customer?->primaryPhone();
    $customerAddress = collect([
        $order->ship_address,
        $order->ship_city,
        $order->ship_state,
        $order->ship_country,
    ])->filter()->implode(', ');
@endphp

<section class="agricart-inbox-invoice" aria-label="{{ __('pos.print.heading') }}">
    <div class="agricart-inbox-invoice__sheet">
        <article class="invoice invoice--a4">
            <header class="invoice__head">
                @if ($storeLogoUrl)
                    <img src="{{ $storeLogoUrl }}" alt="" class="invoice__logo" />
                @endif
                @if (filled($storeName))
                    <h1 class="invoice__store">{{ $storeName }}</h1>
                @endif
                @if (filled($storeAddress))
                    <p class="invoice__meta">{{ $storeAddress }}</p>
                @endif
                <p class="invoice__title">{{ __('pos.print.heading') }}</p>
                <p class="invoice__meta">
                    <strong>{{ $order->order_number }}</strong>
                    · {{ $order->order_date?->format('d M Y') }}
                    @if (filled($storePhone)) · {{ $storePhone }} @endif
                </p>
                @if (filled($customerName))
                    <p class="invoice__meta">{{ __('pos.print.customer') }}: {{ $customerName }}</p>
                @endif
                @if (filled($customerPhone))
                    <p class="invoice__meta">{{ __('online_sale_orders.print.phone') }}: {{ $customerPhone }}</p>
                @endif
                @if (filled($customerAddress))
                    <p class="invoice__meta">{{ __('online_sale_orders.print.ship_to') }}: {{ $customerAddress }}</p>
                @endif
            </header>

            <table class="invoice__table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>{{ __('pos.fields.brand') }}</th>
                        <th class="name">{{ __('pos.fields.product_name') }}</th>
                        <th>{{ __('pos.fields.quantity') }}</th>
                        <th>{{ __('pos.fields.unit_price') }}</th>
                        <th>{{ __('pos.fields.line_total') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($order->items as $index => $item)
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td>{{ $item->displayBrand($locale) }}</td>
                            <td class="name">{{ $item->displayName($locale) }}</td>
                            <td>{{ $item->quantity }}</td>
                            <td>{{ $formatAmount((string) $item->unit_price) }}</td>
                            <td>{{ $formatAmount((string) $item->line_total) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <footer class="invoice__payment">
                <div class="invoice__payment-row"><span>{{ __('pos.fields.subtotal') }}</span><span>{{ $formatAmount((string) $order->subtotal) }}</span></div>
                <div class="invoice__payment-row is-grand"><span>{{ __('pos.fields.total') }}</span><span>{{ $formatAmount((string) $order->total_amount) }}</span></div>
                @if (filled($order->customer_payment_method))
                    <div class="invoice__payment-row"><span>{{ __('online_sale_orders.sections.customer_payment') }}</span><span>{{ __('pos.payment_methods.' . $order->customer_payment_method) }}</span></div>
                @endif
                @if (filled($order->payment_method))
                    <div class="invoice__payment-row"><span>{{ __('pos.fields.payment_method') }}</span><span>{{ __('pos.payment_methods.' . $order->payment_method) }}</span></div>
                @endif
                <div class="invoice__payment-row"><span>{{ __('pos.fields.payment_status') }}</span><span>{{ __('pos.payment_statuses.' . $order->payment_status) }}</span></div>
                <div class="invoice__payment-row"><span>{{ __('pos.fields.amount_paid') }}</span><span>{{ $formatAmount((string) $order->amount_paid) }}</span></div>
                <div class="invoice__payment-row"><span>{{ __('pos.fields.balance_due') }}</span><span>{{ $formatAmount((string) $balanceDue) }}</span></div>
            </footer>

            @if (filled($storeMessage))
                <p class="invoice__message">{{ $storeMessage }}</p>
            @endif
        </article>
    </div>
</section>
