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

                    <div class="ag-store-footer__col ag-store-footer__col--quick">
                        <h3 class="ag-store-footer__heading">
                            {{ $settings->localized('footer_quick_title') ?? __('agricart.storefront.footer.quick_links') }}
                        </h3>
                        <ul class="ag-store-footer__links">
                            <li>
                                <a href="{{ route('store.testimonials') }}" class="ag-store-footer__link">
                                    {{ __('agricart.storefront.testimonials.nav') }}
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('store.brands') }}" class="ag-store-footer__link">
                                    {{ __('agricart.storefront.brands.nav') }}
                                </a>
                            </li>
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

                    <div class="ag-store-footer__col ag-store-footer__col--policies">
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

                    <div class="ag-store-footer__col ag-store-footer__col--legal">
                        <h3 class="ag-store-footer__heading">
                            {{ $settings->localized('footer_legal_title') ?? __('agricart.storefront.footer.legal') }}
                        </h3>
                        <ul class="ag-store-footer__links">
                            @foreach ($footer_legal_pages ?? [] as $page)
                                <li>
                                    <a href="{{ $page->publicUrl() }}" class="ag-store-footer__link">
                                        {{ $page->localizedTitle() }}
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </div>

                    <div class="ag-store-footer__col ag-store-footer__col--registration">
                        <h3 class="ag-store-footer__heading">
                            {{ $settings->localized('footer_registration_title') ?? __('agricart.storefront.footer.registration') }}
                        </h3>
                        <ul class="ag-store-footer__links">
                            <li>
                                <a href="{{ route('auth.staff.register') }}" class="ag-store-footer__link">
                                    {{ __('agricart.storefront.footer.staff') }}
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('auth.supplier.register') }}" class="ag-store-footer__link">
                                    {{ __('agricart.storefront.footer.supplier') }}
                                </a>
                            </li>
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
                        @php($footerMapEmbed = \App\Support\MapEmbedSanitizer::sanitize($settings->footer_map_embed))
                        @if (filled($footerMapEmbed))
                            <div class="ag-store-footer__map">
                                {!! $footerMapEmbed !!}
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
