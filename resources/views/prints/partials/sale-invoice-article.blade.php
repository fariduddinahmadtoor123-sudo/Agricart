{{-- Shared Sales Invoice body (print + inbox). Expects: $sale, $locale, $storeName, $storePhone, $storeAddress, $storeMessage, $storeLogoUrl, $balanceDue, $controlsByProduct, $formatAmount, $isReceipt --}}
@php
    $isReceipt = $isReceipt ?? false;
    $controlsByProduct = $controlsByProduct ?? [];
    $storeAddress = $storeAddress ?? '';
    $onlineOrder = $sale->relationLoaded('onlineSaleOrder')
        ? $sale->onlineSaleOrder
        : $sale->onlineSaleOrder()->first();
    $customerName = $sale->customer?->displayLabel()
        ?: ($onlineOrder?->ship_contact_name ?: null);
    $customerPhone = filled($onlineOrder?->ship_phone)
        ? (string) $onlineOrder->ship_phone
        : ($sale->customer?->primaryPhone() ?: null);
    $customerAddress = collect([
        $onlineOrder?->ship_address,
        $onlineOrder?->ship_city,
        $onlineOrder?->ship_state,
        $onlineOrder?->ship_country,
    ])->filter()->implode(', ');
    if ($customerAddress === '' && $sale->customer) {
        $customerAddress = collect([
            $sale->customer->address_line1 ?? null,
            $sale->customer->city ?? null,
            $sale->customer->state ?? null,
            $sale->customer->country ?? null,
        ])->filter()->implode(', ');
    }
    $declaredPayment = filled($onlineOrder?->customer_payment_method)
        ? (string) $onlineOrder->customer_payment_method
        : null;
@endphp
<article @class(['invoice', 'invoice--a4' => ! $isReceipt, 'invoice--receipt' => $isReceipt])>
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
            <strong>{{ $sale->sale_number }}</strong>
            @if ($onlineOrder?->order_number)
                · {{ $onlineOrder->order_number }}
            @endif
            · {{ $sale->sale_date?->format('d M Y') }}
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
        @if ($sale->cashier)
            <p class="invoice__meta">{{ __('pos.print.cashier') }}: {{ $sale->cashier->name }}</p>
        @endif
    </header>

    @if ($isReceipt)
        <div class="receipt-items">
            @foreach ($sale->items as $index => $item)
                <article class="receipt-item">
                    <p class="receipt-item__name">{{ $index + 1 }}. {{ $item->displayName($locale) }}</p>
                    <div class="receipt-item__grid">
                        <div class="receipt-item__field">
                            <span>{{ __('pos.fields.brand') }}</span>
                            <strong>{{ $item->displayBrand($locale) }}</strong>
                        </div>
                        <div class="receipt-item__field">
                            <span>{{ __('pos.fields.quantity') }}</span>
                            <strong>{{ $item->quantity }}</strong>
                        </div>
                        <div class="receipt-item__field">
                            <span>{{ __('pos.fields.unit_price') }}</span>
                            <strong>{{ $formatAmount((string) $item->unit_price) }}</strong>
                        </div>
                        <div class="receipt-item__field receipt-item__field--total">
                            <span>{{ __('pos.fields.line_total') }}</span>
                            <strong>{{ $formatAmount((string) $item->line_total) }}</strong>
                        </div>
                    </div>
                </article>
            @endforeach
        </div>
    @else
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
                @foreach ($sale->items as $index => $item)
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
    @endif

    @if ($sale->print_controls)
        <section class="invoice__controls">
            <h2>{{ __('pos.print.controls_heading') }}</h2>
            @php $printedControls = false; @endphp
            @foreach ($sale->items as $item)
                @php $list = $controlsByProduct[$item->product_id] ?? []; @endphp
                @if ($list !== [])
                    @php $printedControls = true; @endphp
                    <p><strong>{{ $item->displayName($locale) }}</strong>: {{ implode(' · ', $list) }}</p>
                @endif
            @endforeach
            @if (! $printedControls)
                <p class="invoice__meta">{{ __('pos.print.no_controls') }}</p>
            @endif
        </section>
    @endif

    @if (filled($sale->notes))
        <div class="invoice__notes">
            <strong>{{ __('pos.fields.notes') }}:</strong> {{ $sale->notes }}
        </div>
    @endif

    <footer class="invoice__payment">
        <div class="invoice__payment-row"><span>{{ __('pos.fields.subtotal') }}</span><span>{{ $formatAmount((string) $sale->subtotal) }}</span></div>
        @if ((float) $sale->discount_amount > 0)
            <div class="invoice__payment-row"><span>{{ __('pos.fields.discount') }}</span><span>- {{ $formatAmount((string) $sale->discount_amount) }}</span></div>
        @endif
        <div class="invoice__payment-row is-grand"><span>{{ __('pos.fields.total') }}</span><span>{{ $formatAmount((string) $sale->total_amount) }}</span></div>
        @if (filled($declaredPayment))
            <div class="invoice__payment-row"><span>{{ __('online_sale_orders.sections.customer_payment') }}</span><span>{{ __('pos.payment_methods.' . $declaredPayment) }}</span></div>
        @endif
        @if (filled($sale->payment_method))
            <div class="invoice__payment-row"><span>{{ __('pos.fields.payment_method') }}</span><span>{{ __('pos.payment_methods.' . $sale->payment_method) }}</span></div>
        @endif
        <div class="invoice__payment-row"><span>{{ __('pos.fields.payment_status') }}</span><span>{{ __('pos.payment_statuses.' . $sale->payment_status) }}</span></div>
        <div class="invoice__payment-row"><span>{{ __('pos.fields.amount_paid') }}</span><span>{{ $formatAmount((string) $sale->amount_paid) }}</span></div>
        <div class="invoice__payment-row"><span>{{ __('pos.fields.balance_due') }}</span><span>{{ $formatAmount((string) $balanceDue) }}</span></div>
    </footer>

    @if (filled($storeMessage))
        <p class="invoice__message">{{ $storeMessage }}</p>
    @endif
</article>
