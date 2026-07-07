@extends('store.layouts.app')

@section('title', (request()->query('tab') === 'register'
    ? __('agricart.customer.auth.register_heading')
    : __('agricart.customer.auth.login_heading')) . ' — ' . ($settings->localized('site_name') ?? config('app.name')))

@section('content')
    <div class="ag-store-customer-auth-page">
        <livewire:store.auth.customer-auth />
    </div>
@endsection
