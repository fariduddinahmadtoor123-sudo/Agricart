<div class="ag-store-contact-floats">
    @if ($settings->whatsapp_enabled && filled($settings->whatsappChatUrl()))
        <a
            href="{{ $settings->whatsappChatUrl() }}"
            class="ag-store-whatsapp-float"
            target="_blank"
            rel="noopener noreferrer"
            title="{{ __('agricart.storefront.whatsapp.label') }}"
            aria-label="{{ __('agricart.storefront.whatsapp.label') }}"
        >
            @include('store.components.icons.whatsapp')
        </a>
    @endif

    <button
        type="button"
        class="ag-store-message-float"
        title="{{ __('agricart.storefront.contact.open') }}"
        aria-label="{{ __('agricart.storefront.contact.open') }}"
        data-store-message-open
    >
        @include('store.components.icons.chat')
    </button>
</div>

<dialog
    class="ag-store-message-dialog"
    data-store-message-dialog
    @if ($errors->storeMessage->any() || session('store_message_sent')) data-open="true" @endif
>
    <div class="ag-store-message-dialog__header">
        <div>
            <h2>{{ __('agricart.storefront.contact.title') }}</h2>
            <p>{{ __('agricart.storefront.contact.lead') }}</p>
        </div>
        <button type="button" class="ag-store-message-dialog__close" data-store-message-close aria-label="{{ __('agricart.storefront.contact.close') }}">×</button>
    </div>

    @if (session('store_message_sent'))
        <div class="ag-store-message-dialog__success" role="status">{{ session('store_message_sent') }}</div>
    @else
        <form method="post" action="{{ route('store.contact-message') }}" class="ag-store-message-dialog__form">
            @csrf
            <input type="hidden" name="source_url" value="{{ request()->fullUrl() }}" />
            <div class="ag-store-message-dialog__honeypot" aria-hidden="true">
                <label for="company-website">{{ __('agricart.storefront.contact.website') }}</label>
                <input id="company-website" type="text" name="company_website" value="" tabindex="-1" autocomplete="off" />
            </div>

            <label>
                <span>{{ __('agricart.storefront.contact.name') }}</span>
                <input type="text" name="name" value="{{ old('name') }}" maxlength="120" required autocomplete="name" />
                @error('name', 'storeMessage') <small>{{ $message }}</small> @enderror
            </label>
            <label>
                <span>{{ __('agricart.storefront.contact.phone') }}</span>
                <input type="tel" name="phone" value="{{ old('phone') }}" maxlength="30" required inputmode="tel" autocomplete="tel" />
                @error('phone', 'storeMessage') <small>{{ $message }}</small> @enderror
            </label>
            <label>
                <span>{{ __('agricart.storefront.contact.email') }}</span>
                <input type="email" name="email" value="{{ old('email') }}" maxlength="255" autocomplete="email" />
                @error('email', 'storeMessage') <small>{{ $message }}</small> @enderror
            </label>
            <label>
                <span>{{ __('agricart.storefront.contact.message') }}</span>
                <textarea name="message" rows="5" minlength="5" maxlength="3000" required>{{ old('message') }}</textarea>
                @error('message', 'storeMessage') <small>{{ $message }}</small> @enderror
            </label>

            <button type="submit" class="ag-store-btn ag-store-btn--primary">{{ __('agricart.storefront.contact.send') }}</button>
        </form>
    @endif
</dialog>
