@extends('store.layouts.app')

@section('title', $post->localizedTitle() . ' — ' . ($settings->localized('site_name') ?? config('app.name')))
@section('seo_description', \Illuminate\Support\Str::limit($post->localizedExcerpt() ?: $post->localizedTitle(), 160))
@section('canonical', route('store.blog.show', $post->slug))
@if ($blog->cardImageUrl($post))
    @section('seo_image', $blog->cardImageUrl($post))
@endif

@section('content')
    <article class="ag-store-blog-post">
        <p class="ag-store-blog-post__back">
            <a href="{{ route('store.blog') }}">← {{ __('agricart.storefront.blog.back') }}</a>
        </p>

        <header class="ag-store-blog-post__header">
            <p class="ag-store-blog__meta">
                <span class="ag-store-blog__type">
                    {{ $post->isVideo() ? __('agricart.storefront.blog.type_video') : __('agricart.storefront.blog.type_article') }}
                </span>
                @if ($post->published_at)
                    <time datetime="{{ $post->published_at->toDateString() }}">{{ $post->published_at->format('d M Y') }}</time>
                @endif
            </p>
            <h1 class="ag-store-page-title">{{ $post->localizedTitle() }}</h1>
            @if (filled($post->localizedExcerpt()))
                <p class="ag-store-blog__lead">{{ $post->localizedExcerpt() }}</p>
            @endif
        </header>

        @if ($post->isVideo() && $youtubeId)
            @php $watchUrl = $blog->youtubeWatchUrl($youtubeId); @endphp
            <a
                href="{{ $watchUrl }}"
                class="ag-store-blog__media ag-store-blog__media--video ag-store-blog__media--hero"
                target="_blank"
                rel="noopener noreferrer"
                aria-label="{{ __('agricart.storefront.blog.play_on_youtube', ['title' => $post->localizedTitle()]) }}"
            >
                <img
                    src="{{ $blog->youtubeThumbnailUrl($youtubeId) }}"
                    alt=""
                    class="ag-store-blog__thumb"
                    width="1280"
                    height="720"
                    loading="eager"
                    decoding="async"
                />
                <span class="ag-store-blog__play" aria-hidden="true">▶</span>
            </a>
            <p class="ag-store-blog-post__video-hint">{{ __('agricart.storefront.blog.opens_youtube') }}</p>
        @elseif ($post->coverImageUrl())
            <div class="ag-store-blog__media ag-store-blog__media--hero">
                <img src="{{ $post->coverImageUrl() }}" alt="" class="ag-store-blog__thumb" loading="eager" decoding="async" />
            </div>
        @endif

        @if (filled($post->localizedBody()))
            <div class="ag-store-blog-post__body">
                {!! nl2br(e($post->localizedBody())) !!}
            </div>
        @endif

        @if ($related->isNotEmpty())
            <section class="ag-store-blog-post__related" aria-labelledby="blog-related-heading">
                <h2 id="blog-related-heading" class="ag-store-section-title">{{ __('agricart.storefront.blog.related') }}</h2>
                <div class="ag-store-blog__grid ag-store-blog__grid--related">
                    @foreach ($related as $item)
                        <article class="ag-store-blog__card">
                            <h3 class="ag-store-blog__card-title">
                                <a href="{{ route('store.blog.show', $item->slug) }}">{{ $item->localizedTitle() }}</a>
                            </h3>
                        </article>
                    @endforeach
                </div>
            </section>
        @endif
    </article>
@endsection
