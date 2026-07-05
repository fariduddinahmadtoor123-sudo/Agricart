@extends('store.layouts.app')

@section('title', $page->localizedTitle() . ' — ' . ($settings->localized('site_name') ?? config('app.name')))

@section('content')
    <article class="ag-store-page">
        <div class="ag-store-page__body">
            {!! nl2br(e($page->localizedContent() ?? '')) !!}
        </div>
    </article>
@endsection
