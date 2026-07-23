@extends('store.layouts.app')

@section('title', $conversation->subject)

@section('content')
    <section class="ag-store-account ag-store-account--inbox ag-store-inbox ag-store-inbox--thread">
        <nav class="ag-store-account__nav" aria-label="{{ __('agricart.customer.auth.account_menu') }}">
            <a href="{{ route('store.customer.orders') }}" class="ag-store-orders__link">{{ __('agricart.customer.auth.my_orders') }}</a>
            <a href="{{ route('store.customer.inbox') }}" class="ag-store-orders__link is-active">{{ __('agricart.inbox.nav') }}</a>
            <a href="{{ route('store.customer.account') }}" class="ag-store-orders__link">{{ __('agricart.customer.auth.my_profile') }}</a>
        </nav>

        <header class="ag-store-inbox__head">
            <div>
                <p class="ag-store-inbox__eyebrow">
                    <a href="{{ route('store.customer.inbox') }}">{{ __('agricart.inbox.back') }}</a>
                    · {{ __('agricart.inbox.topics.'.$conversation->topic) }}
                </p>
                <h1 class="ag-store-inbox__title">{{ $conversation->subject }}</h1>
                <p class="ag-store-inbox__lead">{{ __('agricart.inbox.statuses.'.$conversation->status) }}</p>
            </div>
        </header>

        @if (session('customer_auth_status') || session('store_message_sent'))
            <div class="ag-store-alert ag-store-alert--success" role="status">
                {{ session('customer_auth_status') ?: session('store_message_sent') }}
            </div>
        @endif

        <ol class="ag-store-inbox__thread">
            @foreach ($conversation->messages as $message)
                <li @class([
                    'ag-store-inbox__bubble',
                    'is-customer' => $message->isFromCustomer(),
                    'is-admin' => $message->isFromAdmin(),
                    'is-system' => $message->isSystem(),
                ])>
                    <p class="ag-store-inbox__bubble-meta">
                        @if ($message->isSystem())
                            {{ __('agricart.inbox.sender.system') }}
                        @elseif ($message->isFromAdmin())
                            {{ __('agricart.inbox.sender.agricart') }}
                        @else
                            {{ __('agricart.inbox.sender.you') }}
                        @endif
                        · {{ $message->created_at?->format('Y-m-d H:i') }}
                    </p>
                    <p class="ag-store-inbox__bubble-body">{{ $message->body }}</p>
                </li>
            @endforeach
        </ol>

        <form method="post" action="{{ route('store.customer.inbox.reply', $conversation->id) }}" class="ag-store-inbox__reply">
            @csrf
            <label>
                <span>{{ __('agricart.inbox.reply_label') }}</span>
                <textarea name="body" rows="4" maxlength="5000" required>{{ old('body') }}</textarea>
            </label>
            @error('body')
                <p class="ag-store-inbox__errors">{{ $message }}</p>
            @enderror
            <button type="submit" class="ag-store-btn ag-store-btn--primary">{{ __('agricart.inbox.send_reply') }}</button>
        </form>
    </section>
@endsection
