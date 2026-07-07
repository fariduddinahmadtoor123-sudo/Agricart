@if ($settings->footer_enabled)
    <footer class="ag-store-footer" aria-label="{{ __('agricart.storefront.footer.label') }}">
        <div class="ag-store-footer__main">
            <div class="ag-store__container">
                <div class="ag-store-footer__grid">
                    <div class="ag-store-footer__col ag-store-footer__col--logo">
                        @if ($settings->footer_show_logo && filled($footer_logo_url ?? $logo_url))
                            <a href="{{ route('store.home') }}" class="ag-store-footer__logo-link">
                                <img src="{{ $footer_logo_url ?? $logo_url }}" alt="{{ $settings->localized('site_name') ?? config('app.name') }}" class="ag-store-footer__logo" />
                            </a>
                        @endif
                    </div>

                    <div class="ag-store-footer__col ag-store-footer__col--brand">
                        <p class="ag-store-footer__brand-name">{{ $settings->localized('site_name') ?? config('app.name') }}</p>
                        @if (filled($settings->localized('footer_tagline')))
                            <p class="ag-store-footer__tagline">{{ $settings->localized('footer_tagline') }}</p>
                        @endif
                    </div>

                    <div class="ag-store-footer__col">
                        <h3 class="ag-store-footer__heading">
                            {{ $settings->localized('footer_quick_title') ?? __('agricart.storefront.footer.quick_links') }}
                        </h3>
                        <ul class="ag-store-footer__links">
                            @foreach ($footer_quick_entries ?? [] as $entry)
                                <li>
                                    @if ($entry['type'] === 'page')
                                        <a href="{{ $entry['page']->publicUrl() }}" class="ag-store-footer__link">
                                            {{ $entry['page']->localizedTitle() }}
                                        </a>
                                    @else
                                        <a
                                            href="{{ $entry['link']->resolvedUrl() }}"
                                            class="ag-store-footer__link"
                                            @if ($entry['link']->opens_in_new_tab) target="_blank" rel="noopener noreferrer" @endif
                                        >
                                            {{ $entry['link']->localizedLabel() }}
                                        </a>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    </div>

                    <div class="ag-store-footer__col">
                        <h3 class="ag-store-footer__heading">
                            {{ $settings->localized('footer_policies_title') ?? __('agricart.storefront.footer.policies') }}
                        </h3>
                        <ul class="ag-store-footer__links">
                            @foreach ($footer_policy_pages as $page)
                                <li>
                                    <a href="{{ $page->publicUrl() }}" class="ag-store-footer__link">
                                        {{ $page->localizedTitle() }}
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </div>

                    <div class="ag-store-footer__col">
                        <h3 class="ag-store-footer__heading">
                            {{ $settings->localized('footer_registration_title') ?? __('agricart.storefront.footer.registration') }}
                        </h3>
                        <ul class="ag-store-footer__links">
                            @if ($settings->footer_show_login_link)
                                <li>
                                    <a href="{{ $settings->resolvedCustomerLoginUrl() }}" class="ag-store-footer__link">
                                        {{ __('agricart.storefront.header.login') }}
                                    </a>
                                </li>
                            @endif
                            @if ($settings->footer_show_register_link)
                                <li>
                                    <a href="{{ $settings->resolvedRegisterUrl() }}" class="ag-store-footer__link">
                                        {{ __('agricart.storefront.header.register') }}
                                    </a>
                                </li>
                            @endif
                            @foreach ($footer_registration_entries ?? [] as $entry)
                                <li>
                                    @if ($entry['type'] === 'page')
                                        <a href="{{ $entry['page']->publicUrl() }}" class="ag-store-footer__link">
                                            {{ $entry['page']->localizedTitle() }}
                                        </a>
                                    @else
                                        <a
                                            href="{{ $entry['link']->resolvedUrl() }}"
                                            class="ag-store-footer__link"
                                            @if ($entry['link']->opens_in_new_tab) target="_blank" rel="noopener noreferrer" @endif
                                        >
                                            {{ $entry['link']->localizedLabel() }}
                                        </a>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    </div>

                    <div class="ag-store-footer__col ag-store-footer__col--map">
                        @if (filled($settings->footer_map_embed))
                            <div class="ag-store-footer__map">
                                {!! $settings->footer_map_embed !!}
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="ag-store-footer__bottom">
            <div class="ag-store__container ag-store-footer__bottom-inner">
                <p class="ag-store-footer__copyright">{{ $settings->footerCopyrightText() }}</p>
                @if ($settings->footer_show_social)
                    @include('store.components.social-links', [
                        'wrapperClass' => 'ag-store-footer__social',
                        'linkClass' => 'ag-store-footer__social-link',
                    ])
                @endif
            </div>
        </div>
    </footer>
@endif
