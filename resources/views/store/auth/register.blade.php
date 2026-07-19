@extends('store.layouts.app')

@section('title', __('agricart.auth.register_title') . ' — ' . ($settings->localized('site_name') ?? config('app.name')))

@section('content')
    <div class="ag-store-register-page">
        <header class="ag-store-register-page__header">
            <span class="ag-store-register-page__mark" aria-hidden="true">
                <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.8">
                    <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/>
                    <circle cx="9" cy="7" r="4"/>
                    <path d="M19 8v6M22 11h-6"/>
                </svg>
            </span>
            <div>
                <span class="ag-store-register-page__eyebrow">{{ __('agricart.auth.register_kicker') }}</span>
                <h1 class="ag-store-register-page__title">{{ __('agricart.auth.register_title') }}</h1>
                <p class="ag-store-register-page__lead">{{ __('agricart.auth.register_lead') }}</p>
            </div>
        </header>

        <livewire:auth.register-form />
    </div>
@endsection
