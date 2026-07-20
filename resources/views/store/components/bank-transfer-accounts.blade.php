@php
    /** @var \Illuminate\Support\Collection<int, \App\Models\BankAccount>|\Illuminate\Database\Eloquent\Collection<int, \App\Models\BankAccount> $accounts */
    $accounts = $accounts ?? collect();
    $heading = $heading ?? __('agricart.storefront.checkout.bank_details_heading');
    $lead = $lead ?? __('agricart.storefront.checkout.bank_payment_accounts_lead');
@endphp

@if ($accounts->isNotEmpty())
    <div class="ag-store-bank-accounts">
        <h3 class="ag-store-bank-accounts__heading">{{ $heading }}</h3>
        @if (filled($lead))
            <p class="ag-store-bank-accounts__lead">{{ $lead }}</p>
        @endif
        <div class="ag-store-bank-accounts__list">
            @foreach ($accounts as $account)
                @php
                    $identifier = $account->displayAccountIdentifier();
                @endphp
                <article class="ag-store-bank-accounts__card">
                    <strong class="ag-store-bank-accounts__bank">{{ $account->displayBankName() ?: $account->displayName() }}</strong>
                    <span class="ag-store-bank-accounts__title">{{ $account->account_title }}</span>
                    @if ($identifier !== '')
                        <span class="ag-store-bank-accounts__copy-row">
                            <code class="ag-store-bank-accounts__code">{{ $identifier }}</code>
                            <button
                                type="button"
                                class="ag-store-bank-accounts__copy"
                                data-copy-value="{{ $identifier }}"
                                data-copy-success="{{ __('agricart.storefront.accessibility.copied') }}"
                            >{{ __('agricart.storefront.accessibility.copy') }}</button>
                        </span>
                    @endif
                </article>
            @endforeach
        </div>
    </div>
@endif
