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
