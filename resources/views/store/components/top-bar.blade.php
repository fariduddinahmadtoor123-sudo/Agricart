<div class="ag-store-topbar">
    <div class="ag-store__container">
        <div class="ag-store-topbar__inner">
            <div class="ag-store-topbar__left">{{ $settings->localized('top_bar_left') }}</div>
            <div class="ag-store-topbar__center">{{ $settings->localized('top_bar_center') }}</div>
            <div class="ag-store-topbar__right">
                <div class="ag-store-topbar__contact">
                    @if (filled($settings->top_bar_phone))
                        <a href="tel:{{ preg_replace('/[^\d+]/', '', $settings->top_bar_phone) }}" class="ag-store-topbar__phone">
                            {{ $settings->top_bar_phone }}
                        </a>
                    @elseif (filled($settings->localized('top_bar_right')))
                        <span class="ag-store-topbar__text">{{ $settings->localized('top_bar_right') }}</span>
                    @endif

                    @include('store.components.locale-switcher', [
                        'wrapperClass' => 'ag-store-topbar__locale',
                    ])

                    @if ($settings->show_social_top_bar)
                        @include('store.components.social-links', [
                            'wrapperClass' => 'ag-store-topbar__social',
                            'linkClass' => 'ag-store-topbar__social-link',
                        ])
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
