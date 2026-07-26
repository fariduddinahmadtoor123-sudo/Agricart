@php
    $customer = auth('customer')->user();
    $isCustomer = $customer instanceof \App\Models\Customer;
    $redirectPath = request()->getRequestUri();
    $loginUrl = route('store.customer.login', ['redirect' => $redirectPath]);
    $registerUrl = route('store.customer.login', ['tab' => 'register', 'redirect' => $redirectPath]);
    $inboxUnread = $isCustomer
        ? app(\App\Services\Store\AgricartInboxService::class)->unreadCountForCustomer($customer)
        : 0;
@endphp

<div class="ag-store-contact-floats">
    <button
        type="button"
        class="ag-store-message-float"
        title="{{ __('agricart.storefront.contact.open') }}"
        aria-label="{{ __('agricart.storefront.contact.open') }}"
        data-store-message-open
    >
        @include('store.components.icons.chat')
        <span class="ag-store-message-float__label">{{ __('agricart.storefront.contact.fab_label') }}</span>
        @if ($inboxUnread > 0)
            <span class="ag-store-message-float__badge" aria-label="{{ __('agricart.inbox.unread_count', ['count' => $inboxUnread]) }}">
                {{ $inboxUnread > 9 ? '9+' : $inboxUnread }}
            </span>
        @endif
    </button>
</div>

@if ($isCustomer)
    <dialog
        class="ag-store-message-dialog"
        data-store-message-dialog
        @if ($errors->storeMessage->any() || session('store_message_sent')) data-open="true" @endif
    >
        <div class="ag-store-message-dialog__header">
            <div class="ag-store-message-dialog__brand">
                <strong class="ag-store-message-dialog__eyebrow">{{ __('agricart.storefront.contact.fab_label') }}</strong>
                <h2>{{ __('agricart.storefront.contact.title') }}</h2>
                <p>{{ __('agricart.storefront.contact.lead') }}</p>
            </div>
            <button type="button" class="ag-store-message-dialog__close" data-store-message-close aria-label="{{ __('agricart.storefront.contact.close') }}">×</button>
        </div>

        @if (session('store_message_sent'))
            <div class="ag-store-message-dialog__success" role="status">{{ session('store_message_sent') }}</div>
            <div class="ag-store-message-dialog__gate-actions">
                <a href="{{ route('store.customer.inbox') }}" class="ag-store-btn ag-store-btn--primary">{{ __('agricart.storefront.contact.view_inbox') }}</a>
            </div>
        @else
            <form method="post" action="{{ route('store.contact-message') }}" class="ag-store-message-dialog__form">
                @csrf
                <input type="hidden" name="source_url" value="{{ request()->fullUrl() }}" />
                <div class="ag-store-message-dialog__honeypot" aria-hidden="true">
                    <label for="company-website">{{ __('agricart.storefront.contact.website') }}</label>
                    <input id="company-website" type="text" name="company_website" value="" tabindex="-1" autocomplete="off" />
                </div>

                <label>
                    <span>{{ __('agricart.storefront.contact.message') }}</span>
                    <textarea name="message" rows="5" minlength="5" maxlength="3000" required autofocus>{{ old('message') }}</textarea>
                    @error('message', 'storeMessage') <small>{{ $message }}</small> @enderror
                </label>

                <div class="ag-store-message-dialog__gate-actions">
                    <button type="submit" class="ag-store-btn ag-store-btn--primary">{{ __('agricart.storefront.contact.send') }}</button>
                    <a href="{{ route('store.customer.inbox') }}" class="ag-store-btn ag-store-btn--secondary">
                        {{ __('agricart.storefront.contact.view_inbox') }}
                        @if ($inboxUnread > 0)
                            <span class="ag-store-message-dialog__inbox-badge">{{ $inboxUnread > 99 ? '99+' : $inboxUnread }}</span>
                        @endif
                    </a>
                </div>
            </form>
        @endif
    </dialog>
@else
    <dialog
        class="ag-store-message-dialog ag-store-message-dialog--gate"
        data-store-message-dialog
        data-store-message-gate
    >
        <div class="ag-store-message-dialog__header">
            <div class="ag-store-message-dialog__brand">
                <strong class="ag-store-message-dialog__eyebrow">{{ __('agricart.storefront.contact.fab_label') }}</strong>
                <h2>{{ __('agricart.storefront.contact.gate_title') }}</h2>
                <p>{{ __('agricart.storefront.contact.gate_lead') }}</p>
            </div>
            <button type="button" class="ag-store-message-dialog__close" data-store-message-close aria-label="{{ __('agricart.storefront.contact.close') }}">×</button>
        </div>

        <div class="ag-store-message-dialog__gate-actions">
            <a href="{{ $loginUrl }}" class="ag-store-btn ag-store-btn--primary">{{ __('agricart.storefront.contact.gate_login') }}</a>
            <a href="{{ $registerUrl }}" class="ag-store-btn ag-store-btn--secondary">{{ __('agricart.storefront.contact.gate_register') }}</a>
        </div>
    </dialog>
@endif
