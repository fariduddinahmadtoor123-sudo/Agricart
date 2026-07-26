@php
    $gtmId = trim((string) ($settings->gtm_container_id ?? ''));
@endphp
@if ($gtmId !== '')
    <noscript>
        <iframe src="https://www.googletagmanager.com/ns.html?id={{ urlencode($gtmId) }}" height="0" width="0" style="display:none;visibility:hidden" title="Google Tag Manager"></iframe>
    </noscript>
@endif
