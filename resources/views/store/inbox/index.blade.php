@extends('store.layouts.app')

@section('title', __('agricart.inbox.page_title'))

@section('content')
    <section class="ag-store-account ag-store-account--inbox ag-store-inbox">
        <nav class="ag-store-account__nav" aria-label="{{ __('agricart.customer.auth.account_menu') }}">
            <a href="{{ route('store.customer.orders') }}" class="ag-store-orders__link">{{ __('agricart.customer.auth.my_orders') }}</a>
            <a href="{{ route('store.customer.inbox') }}" class="ag-store-orders__link is-active">{{ __('agricart.inbox.nav') }}</a>
            <a href="{{ route('store.customer.account') }}" class="ag-store-orders__link">{{ __('agricart.customer.auth.my_profile') }}</a>
        </nav>

        <header class="ag-store-inbox__head">
            <div>
                <h1 class="ag-store-inbox__title">{{ __('agricart.inbox.heading') }}</h1>
                <p class="ag-store-inbox__lead">{{ __('agricart.inbox.lead') }}</p>
            </div>
            @if (($unreadCount ?? 0) > 0)
                <p class="ag-store-inbox__badge">{{ __('agricart.inbox.unread_count', ['count' => $unreadCount]) }}</p>
            @endif
        </header>

        @if (session('customer_auth_status') || session('store_message_sent'))
            <div class="ag-store-alert ag-store-alert--success" role="status">
                {{ session('customer_auth_status') ?: session('store_message_sent') }}
            </div>
        @endif

        <details class="ag-store-inbox__compose">
            <summary>{{ __('agricart.inbox.new_message') }}</summary>
            <form method="post" action="{{ route('store.customer.inbox.store') }}" class="ag-store-inbox__compose-form">
                @csrf
                <label>
                    <span>{{ __('agricart.inbox.subject_label') }}</span>
                    <input type="text" name="subject" value="{{ old('subject') }}" maxlength="200" required />
                </label>
                <label>
                    <span>{{ __('agricart.inbox.body_label') }}</span>
                    <textarea name="body" rows="4" maxlength="5000" required>{{ old('body') }}</textarea>
                </label>
                @if ($errors->any())
                    <ul class="ag-store-inbox__errors">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                @endif
                <button type="submit" class="ag-store-btn ag-store-btn--primary">{{ __('agricart.inbox.send') }}</button>
            </form>
        </details>

        @if ($conversations->isEmpty())
            <p class="ag-store-inbox__empty">{{ __('agricart.inbox.empty') }}</p>
        @else
            <ul class="ag-store-inbox__list">
                @foreach ($conversations as $conversation)
                    @php $customerState = $conversation->customerQueueState(); @endphp
                    <li @class(['ag-store-inbox__item', 'is-unread' => $conversation->unreadForCustomer()])>
                        <a href="{{ route('store.customer.inbox.show', $conversation->id) }}" class="ag-store-inbox__link">
                            <span class="ag-store-inbox__item-topic">{{ __('agricart.inbox.topics.'.$conversation->topic) }}</span>
                            <strong class="ag-store-inbox__item-subject">{{ $conversation->subject }}</strong>
                            <span class="ag-store-inbox__item-meta">
                                {{ agricart_datetime($conversation->last_message_at ?: $conversation->created_at) }}
                            </span>
                            <span class="ag-store-inbox__item-state is-{{ $customerState }}">
                                {{ __('agricart.inbox.customer_queue.'.$customerState) }}
                            </span>
                        </a>
                    </li>
                @endforeach
            </ul>
            <div class="ag-store-inbox__pagination">{{ $conversations->links() }}</div>
        @endif
    </section>
@endsection
