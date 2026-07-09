@php
    $customer = auth('customer')->user();
    $initials = strtoupper(
        substr($customer->first_name ?: $customer->displayLabel(), 0, 1)
        . substr($customer->last_name ?: '', 0, 1)
    );
    $initials = filled(trim($initials)) ? trim($initials) : strtoupper(substr($customer->displayLabel(), 0, 2));
@endphp
<details class="ag-store-header__user ag-store-user-details">
    <summary class="ag-store-header__user-trigger" aria-label="{{ __('agricart.customer.auth.account_menu') }}">
        <span class="ag-store-header__user-avatar" aria-hidden="true">{{ $initials }}</span>
        <span class="ag-store-header__user-name">{{ $customer->displayLabel() }}</span>
        <span class="ag-store-header__user-chevron" aria-hidden="true">
            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="square">
                <path d="M6 9l6 6 6-6"/>
            </svg>
        </span>
    </summary>
    <div class="ag-store-header__user-menu">
        <p class="ag-store-header__user-email">{{ $customer->email }}</p>
        <a href="{{ route('store.customer.account') }}" class="ag-store-header__user-link">
            {{ __('agricart.customer.auth.my_profile') }}
        </a>
        <form method="post" action="{{ route('store.customer.logout') }}" class="ag-store-header__user-logout">
            @csrf
            <button type="submit" class="ag-store-header__user-link ag-store-header__user-link--logout">
                {{ __('agricart.customer.auth.logout') }}
            </button>
        </form>
    </div>
</details>
