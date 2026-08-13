@php
    /** @var \Illuminate\Support\Collection<int, \App\Models\BankAccount>|\Illuminate\Database\Eloquent\Collection<int, \App\Models\BankAccount> $accounts */
    $accounts = $accounts ?? collect();
    $heading = $heading ?? __('agricart.storefront.checkout.bank_details_heading');
    $lead = $lead ?? __('agricart.storefront.checkout.bank_payment_accounts_lead');
    $copyLabel = __('agricart.storefront.accessibility.copy');
    $copiedLabel = __('agricart.storefront.accessibility.copied');
@endphp

@if ($accounts->isNotEmpty())
    <div class="ag-store-bank-accounts">
        @if (filled($heading))
            <h3 class="ag-store-bank-accounts__heading">{{ $heading }}</h3>
        @endif
        @if (filled($lead))
            <p class="ag-store-bank-accounts__lead">{{ $lead }}</p>
        @endif
        <p class="ag-store-bank-accounts__legend">{{ __('agricart.storefront.checkout.bank_line_legend') }}</p>
        <div class="ag-store-bank-accounts__list">
            @foreach ($accounts as $account)
                @php
                    $bankName = $account->displayBankName() ?: $account->displayName();
                    $accountTitle = (string) ($account->account_title ?? '');
                    $identifier = $account->displayAccountIdentifier();
                    $lineParts = array_values(array_filter([
                        filled($bankName) ? $bankName : null,
                        filled($accountTitle) ? $accountTitle : null,
                        $identifier !== '' ? $identifier : null,
                    ], static fn (?string $part): bool => filled($part)));
                    $lineText = implode(' · ', $lineParts);
                    $allParts = array_values(array_filter([
                        filled($bankName) ? __('agricart.storefront.checkout.bank_copy_bank', ['value' => $bankName]) : null,
                        filled($accountTitle) ? __('agricart.storefront.checkout.bank_copy_title', ['value' => $accountTitle]) : null,
                        $identifier !== '' ? __('agricart.storefront.checkout.bank_copy_account', ['value' => $identifier]) : null,
                    ]));
                    $allText = implode("\n", $allParts);
                @endphp
                <article class="ag-store-bank-accounts__card">
                    <div class="ag-store-bank-accounts__line">
                        <p class="ag-store-bank-accounts__line-text">
                            @if (filled($bankName))
                                <strong class="ag-store-bank-accounts__bank">{{ $bankName }}</strong>
                            @endif
                            @if (filled($accountTitle))
                                <span class="ag-store-bank-accounts__sep" aria-hidden="true">·</span>
                                <span class="ag-store-bank-accounts__title">{{ $accountTitle }}</span>
                            @endif
                            @if ($identifier !== '')
                                <span class="ag-store-bank-accounts__sep" aria-hidden="true">·</span>
                                <code class="ag-store-bank-accounts__code">{{ $identifier }}</code>
                            @endif
                        </p>
                        @if ($lineText !== '')
                            <button
                                type="button"
                                class="ag-store-bank-accounts__copy"
                                data-copy-value="{{ $lineText }}"
                                data-copy-success="{{ $copiedLabel }}"
                                data-copy-label="{{ $copyLabel }}"
                            >{{ $copyLabel }}</button>
                        @endif
                    </div>

                    @if ($allText !== '')
                        <button
                            type="button"
                            class="ag-store-bank-accounts__copy ag-store-bank-accounts__copy--all"
                            data-copy-value="{{ $allText }}"
                            data-copy-success="{{ $copiedLabel }}"
                            data-copy-label="{{ __('agricart.storefront.checkout.bank_copy_all') }}"
                        >{{ __('agricart.storefront.checkout.bank_copy_all') }}</button>
                    @endif
                </article>
            @endforeach
        </div>
    </div>
@endif
