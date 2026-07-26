@extends('store.layouts.app')

@section('title', __('agricart.storefront.about.page_title') . ' — ' . ($settings->localized('site_name') ?? config('app.name')))
@section('seo_description', \Illuminate\Support\Str::limit(trim(strip_tags($story ?? __('agricart.storefront.about.page_lead'))), 160))
@section('canonical', route('store.about'))
@section('robots', 'index,follow,max-image-preview:large')
@push('structured-data')
    <script type="application/ld+json">{!! app(\App\Services\Store\StorefrontSchemaService::class)->toJson($schemaDocument) !!}</script>
@endpush

@section('content')
    <article class="ag-store-about">
        <header class="ag-store-about__hero">
            <p class="ag-store-about__eyebrow">{{ __('agricart.storefront.about.hero_eyebrow') }}</p>
            <h1 class="ag-store-about__title">{{ $siteName }}</h1>
            @if (filled($tagline))
                <p class="ag-store-about__tagline">{{ $tagline }}</p>
            @endif
            @if (filled($heroYears))
                <p class="ag-store-about__years">{{ $heroYears }}</p>
            @endif
        </header>

        @if (filled($story))
            <section class="ag-store-about__section ag-store-about__story" aria-labelledby="about-story-heading">
                <h2 id="about-story-heading" class="ag-store-about__heading">{{ __('agricart.storefront.about.story_heading') }}</h2>
                <div class="ag-store-about__prose">{!! nl2br(e($story)) !!}</div>
            </section>
        @endif

        @if ($hasCeo)
            <section class="ag-store-about__section ag-store-about__ceo" aria-labelledby="about-ceo-heading">
                <h2 id="about-ceo-heading" class="ag-store-about__heading">{{ __('agricart.storefront.about.ceo_heading') }}</h2>
                <div class="ag-store-about__ceo-card">
                    <div class="ag-store-about__ceo-photo">
                        @if ($ceoPhotoUrl)
                            <img src="{{ $ceoPhotoUrl }}" alt="{{ $ceoName ?? '' }}" width="320" height="320" loading="lazy" />
                        @else
                            <span class="ag-store-about__photo-fallback">{{ __('agricart.storefront.about.photo_placeholder') }}</span>
                        @endif
                    </div>
                    <div class="ag-store-about__ceo-copy">
                        @if (filled($ceoName))
                            <h3 class="ag-store-about__ceo-name">{{ $ceoName }}</h3>
                        @endif
                        @if (filled($ceoTitle))
                            <p class="ag-store-about__ceo-title">{{ $ceoTitle }}</p>
                        @endif
                        @if (filled($ceoBio))
                            <div class="ag-store-about__prose">{!! nl2br(e($ceoBio)) !!}</div>
                        @endif
                    </div>
                </div>
            </section>
        @endif

        @if ($hasOffice)
            <section class="ag-store-about__section ag-store-about__office" aria-labelledby="about-office-heading">
                <h2 id="about-office-heading" class="ag-store-about__heading">{{ __('agricart.storefront.about.office_heading') }}</h2>
                <div class="ag-store-about__gallery">
                    @foreach ($officeGallery as $image)
                        <figure class="ag-store-about__gallery-item">
                            <img src="{{ $image['url'] }}" alt="{{ $image['caption'] ?? __('agricart.storefront.about.office_heading') }}" loading="lazy" />
                            @if (filled($image['caption']))
                                <figcaption>{{ $image['caption'] }}</figcaption>
                            @endif
                        </figure>
                    @endforeach
                </div>
            </section>
        @endif

        @if ($hasTeam)
            <section class="ag-store-about__section ag-store-about__team" aria-labelledby="about-team-heading">
                <h2 id="about-team-heading" class="ag-store-about__heading">{{ __('agricart.storefront.about.team_heading') }}</h2>
                <div class="ag-store-about__team-grid">
                    @foreach ($teamRoster as $member)
                        <article class="ag-store-about__team-card">
                            <div class="ag-store-about__team-photo">
                                @if ($member['photo_url'])
                                    <img src="{{ $member['photo_url'] }}" alt="{{ $member['name'] }}" width="160" height="160" loading="lazy" />
                                @else
                                    <span aria-hidden="true">{{ strtoupper(mb_substr($member['name'], 0, 1)) }}</span>
                                @endif
                            </div>
                            <h3 class="ag-store-about__team-name">{{ $member['name'] }}</h3>
                            @if (filled($member['role']))
                                <p class="ag-store-about__team-role">{{ $member['role'] }}</p>
                            @endif
                        </article>
                    @endforeach
                </div>
            </section>
        @endif

        <section class="ag-store-about__section ag-store-about__cta" aria-labelledby="about-cta-heading">
            <h2 id="about-cta-heading" class="ag-store-about__heading">{{ __('agricart.storefront.about.cta_heading') }}</h2>
            <p class="ag-store-about__cta-lead">{{ __('agricart.storefront.about.cta_lead') }}</p>
            <div class="ag-store-about__cta-actions">
                <a href="{{ route('store.testimonials') }}" class="ag-store-btn ag-store-btn--primary">
                    {{ __('agricart.storefront.about.cta_reviews') }}
                </a>
                <button type="button" class="ag-store-btn ag-store-btn--secondary" data-store-message-open>
                    {{ __('agricart.storefront.about.cta_contact') }}
                </button>
            </div>
        </section>
    </article>
@endsection
