@extends('store.layouts.app')

@section('title', __('agricart.supplier_register.title') . ' — ' . ($settings->localized('site_name') ?? config('app.name')))

@section('content')
    <div class="ag-store-register-page">
        <header class="ag-store-register-page__header">
            <span class="ag-store-register-page__mark" aria-hidden="true">
                <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.8">
                    <path d="M3 9h18v11H3z"/>
                    <path d="M3 9V7a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v2"/>
                    <path d="M8 13h2M14 13h2"/>
                </svg>
            </span>
            <div>
                <span class="ag-store-register-page__eyebrow">{{ __('agricart.supplier_register.kicker') }}</span>
                <h1 class="ag-store-register-page__title">{{ __('agricart.supplier_register.title') }}</h1>
                <p class="ag-store-register-page__lead">{{ __('agricart.supplier_register.lead') }}</p>
            </div>
        </header>

        <livewire:auth.supplier-register-form />
    </div>
@endsection
