@if ($hasBusinessContact ?? ($businessContact['hasContent'] ?? false))
    @php($contact = $businessContact ?? [])
    <section class="ag-store-about__section ag-store-about__business" aria-labelledby="about-business-heading">
        <h2 id="about-business-heading" class="ag-store-about__heading">
            {{ __('agricart.storefront.about.business_heading') }}
        </h2>
        <p class="ag-store-about__business-lead">{{ __('agricart.storefront.about.business_lead') }}</p>

        <div class="ag-store-about__business-grid">
            <div class="ag-store-about__business-contact">
                @foreach ($contact['addresses'] ?? [] as $address)
                    <article @class(['ag-store-about__business-card', 'is-primary' => ! empty($address['is_primary'])])>
                        <span class="ag-store-about__business-icon" aria-hidden="true">📍</span>
                        <div>
                            <h3 class="ag-store-about__business-label">
                                {{ __('agricart.storefront.about.address_label') }}
                                @if (! empty($address['is_primary']))
                                    <span class="ag-store-about__business-badge">{{ __('agricart.storefront.about.primary_badge') }}</span>
                                @endif
                            </h3>
                            <p class="ag-store-about__business-value">{{ $address['text'] }}</p>
                        </div>
                    </article>
                @endforeach

                @foreach ($contact['phones'] ?? [] as $phone)
                    <article @class(['ag-store-about__business-card', 'is-primary' => ! empty($phone['is_primary'])])>
                        <span class="ag-store-about__business-icon" aria-hidden="true">📞</span>
                        <div>
                            <h3 class="ag-store-about__business-label">
                                {{ $phone['label'] }}
                                @if (! empty($phone['is_primary']))
                                    <span class="ag-store-about__business-badge">{{ __('agricart.storefront.about.primary_badge') }}</span>
                                @endif
                            </h3>
                            @if (filled($phone['person'] ?? null))
                                <p class="ag-store-about__business-meta">{{ $phone['person'] }}</p>
                            @endif
                            <a href="tel:{{ $phone['tel'] }}" class="ag-store-about__business-link">{{ $phone['number'] }}</a>
                        </div>
                    </article>
                @endforeach

                @if (filled($contact['email'] ?? null))
                    <article class="ag-store-about__business-card">
                        <span class="ag-store-about__business-icon" aria-hidden="true">✉️</span>
                        <div>
                            <h3 class="ag-store-about__business-label">{{ __('agricart.storefront.about.email_label') }}</h3>
                            <a href="mailto:{{ $contact['email'] }}" class="ag-store-about__business-link">{{ $contact['email'] }}</a>
                        </div>
                    </article>
                @endif

                @if (filled($contact['whatsapp']['url'] ?? null))
                    <article class="ag-store-about__business-card ag-store-about__business-card--whatsapp">
                        <span class="ag-store-about__business-icon" aria-hidden="true">💬</span>
                        <div>
                            <h3 class="ag-store-about__business-label">{{ __('agricart.storefront.about.whatsapp_label') }}</h3>
                            <a
                                href="{{ $contact['whatsapp']['url'] }}"
                                class="ag-store-btn ag-store-btn--whatsapp"
                                target="_blank"
                                rel="noopener noreferrer"
                            >
                                {{ $contact['whatsapp']['display'] }}
                            </a>
                        </div>
                    </article>
                @endif

                @if (filled($contact['website']['url'] ?? null))
                    <article class="ag-store-about__business-card">
                        <span class="ag-store-about__business-icon" aria-hidden="true">🌐</span>
                        <div>
                            <h3 class="ag-store-about__business-label">{{ __('agricart.storefront.about.website_label') }}</h3>
                            <a
                                href="{{ $contact['website']['url'] }}"
                                class="ag-store-about__business-link"
                                target="_blank"
                                rel="noopener noreferrer"
                            >
                                {{ $contact['website']['label'] }}
                            </a>
                        </div>
                    </article>
                @endif
            </div>

            @if (filled($contact['ntn'] ?? null) || filled($contact['strn'] ?? null) || filled($contact['taxCertificateUrl'] ?? null))
                <aside class="ag-store-about__business-tax" aria-labelledby="about-tax-heading">
                    <h3 id="about-tax-heading" class="ag-store-about__business-tax-title">
                        {{ __('agricart.storefront.about.tax_heading') }}
                    </h3>

                    @if (filled($contact['ntn'] ?? null))
                        <dl class="ag-store-about__tax-row">
                            <dt>{{ __('agricart.storefront.about.ntn_label') }}</dt>
                            <dd>{{ $contact['ntn'] }}</dd>
                        </dl>
                    @endif

                    @if (filled($contact['strn'] ?? null))
                        <dl class="ag-store-about__tax-row">
                            <dt>{{ __('agricart.storefront.about.strn_label') }}</dt>
                            <dd>{{ $contact['strn'] }}</dd>
                        </dl>
                    @endif

                    @if (filled($contact['taxCertificateUrl'] ?? null))
                        <figure class="ag-store-about__tax-certificate">
                            <a href="{{ $contact['taxCertificateUrl'] }}" target="_blank" rel="noopener noreferrer">
                                <img
                                    src="{{ $contact['taxCertificateUrl'] }}"
                                    alt="{{ __('agricart.storefront.about.tax_certificate_alt') }}"
                                    loading="lazy"
                                    decoding="async"
                                />
                            </a>
                            <figcaption>{{ __('agricart.storefront.about.tax_certificate_caption') }}</figcaption>
                        </figure>
                    @endif
                </aside>
            @endif
        </div>
    </section>
@endif
