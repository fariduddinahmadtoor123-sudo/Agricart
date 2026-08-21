@extends('store.layouts.app')

@section('title', __('agricart.storefront.blog.page_title') . ' — ' . ($settings->localized('site_name') ?? config('app.name')))
@section('seo_description', __('agricart.storefront.blog.page_lead'))
@section('canonical', route('store.blog'))

@section('content')
    <section class="ag-store-blog">
        <header class="ag-store-blog__header">
            <h1 class="ag-store-page-title">{{ __('agricart.storefront.blog.heading') }}</h1>
            <p class="ag-store-blog__lead">{{ __('agricart.storefront.blog.page_lead') }}</p>
        </header>

        @if ($posts->isEmpty())
            <p class="ag-store-blog__empty">{{ __('agricart.storefront.blog.empty') }}</p>
        @else
            <div class="ag-store-blog__grid">
                @foreach ($posts as $post)
                    @php
                        $image = $blog->cardImageUrl($post);
                        $watchUrl = $post->isVideo()
                            ? $blog->youtubeWatchUrl($blog->extractYoutubeId($post->youtube_url))
                            : null;
                    @endphp
                    <article class="ag-store-blog__card">
                        @if ($post->isVideo() && $watchUrl)
                            <a
                                href="{{ $watchUrl }}"
                                class="ag-store-blog__media ag-store-blog__media--video"
                                target="_blank"
                                rel="noopener noreferrer"
                                aria-label="{{ __('agricart.storefront.blog.play_on_youtube', ['title' => $post->localizedTitle()]) }}"
                            >
                                @if ($image)
                                    <img src="{{ $image }}" alt="" class="ag-store-blog__thumb" loading="lazy" decoding="async" width="640" height="360" />
                                @else
                                    <span class="ag-store-blog__thumb-placeholder"></span>
                                @endif
                                <span class="ag-store-blog__play" aria-hidden="true">▶</span>
                            </a>
                        @else
                            <a href="{{ route('store.blog.show', $post->slug) }}" class="ag-store-blog__media">
                                @if ($image)
                                    <img src="{{ $image }}" alt="" class="ag-store-blog__thumb" loading="lazy" decoding="async" width="640" height="360" />
                                @else
                                    <span class="ag-store-blog__thumb-placeholder"></span>
                                @endif
                            </a>
                        @endif

                        <div class="ag-store-blog__card-body">
                            <p class="ag-store-blog__meta">
                                <span class="ag-store-blog__type">
                                    {{ $post->isVideo() ? __('agricart.storefront.blog.type_video') : __('agricart.storefront.blog.type_article') }}
                                </span>
                                @if ($post->published_at)
                                    <time datetime="{{ $post->published_at->toDateString() }}">{{ $post->published_at->format('d M Y') }}</time>
                                @endif
                            </p>
                            <h2 class="ag-store-blog__card-title">
                                <a href="{{ route('store.blog.show', $post->slug) }}">{{ $post->localizedTitle() }}</a>
                            </h2>
                            @if (filled($post->localizedExcerpt()))
                                <p class="ag-store-blog__excerpt">{{ $post->localizedExcerpt() }}</p>
                            @endif
                            <a href="{{ route('store.blog.show', $post->slug) }}" class="ag-store-blog__more">
                                {{ __('agricart.storefront.blog.read_more') }}
                            </a>
                        </div>
                    </article>
                @endforeach
            </div>

            <div class="ag-store-blog__pagination">
                {{ $posts->links() }}
            </div>
        @endif
    </section>
@endsection
