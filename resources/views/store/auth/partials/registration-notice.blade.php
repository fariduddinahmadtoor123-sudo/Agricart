@if ($hasNotice ?? false)
    <aside class="ag-store-register-notice" aria-label="{{ $title ?? __('agricart.storefront.footer.registration') }}">
        @if (filled($title ?? null))
            <h2 class="ag-store-register-notice__title">{{ $title }}</h2>
        @endif
        @if (filled($bodyHtml ?? null))
            <div class="ag-store-register-notice__body">
                {!! $bodyHtml !!}
            </div>
        @endif
    </aside>
@endif
