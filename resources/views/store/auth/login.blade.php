@extends('store.layouts.app')

@section('title', (($authTab ?? request()->query('tab')) === 'register'
    ? __('agricart.customer.auth.register_heading')
    : __('agricart.customer.auth.login_heading')) . ' — ' . ($settings->localized('site_name') ?? config('app.name')))

@section('content')
    <div class="ag-store-customer-auth-page">
        @include('store.auth.customer-auth-forms', ['authTab' => $authTab ?? null])
    </div>
@endsection
