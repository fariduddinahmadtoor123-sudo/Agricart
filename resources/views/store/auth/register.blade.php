@extends('store.layouts.app')

@section('title', __('agricart.auth.register_title') . ' — ' . ($settings->localized('site_name') ?? config('app.name')))

@section('content')
    <div class="ag-store-register-page">
        <header class="ag-store-register-page__header">
            <h1 class="ag-store-register-page__title">{{ __('agricart.auth.register_title') }}</h1>
            <p class="ag-store-register-page__lead">{{ __('agricart.auth.register_lead') }}</p>
        </header>

        <livewire:auth.register-form />
    </div>
@endsection
