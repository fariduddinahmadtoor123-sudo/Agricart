@extends('store.layouts.app')

@section('title', __('agricart.customer.returns.page_title', ['number' => $order->order_number]) . ' — ' . ($settings->localized('site_name') ?? config('app.name')))

@section('content')
    <div class="ag-store-customer-auth-page">
        <div class="ag-store-account ag-store-account--orders ag-store-orders__detail">
            <header class="ag-store-orders__detail-head">
                <div class="ag-store-orders__detail-toolbar">
                    <a href="{{ route('store.customer.orders.show', $order->order_number) }}" class="ag-store-orders__link">{{ __('agricart.customer.returns.back_to_order') }}</a>
                </div>
                <h1 class="ag-store-customer-auth__title">{{ __('agricart.customer.returns.heading', ['number' => $order->order_number]) }}</h1>
                <p class="ag-store-customer-auth__lead">{{ __('agricart.customer.returns.lead') }}</p>
            </header>

            @if (session('customer_auth_status'))
                <div class="ag-store-customer-auth__alert is-success" role="status">
                    {{ session('customer_auth_status') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="ag-store-customer-auth__alert is-danger" role="alert">
                    {{ $errors->first() }}
                </div>
            @endif

            @if ($pending)
                <section class="ag-store-orders__detail-card ag-store-return">
                    <p class="ag-store-return__status">
                        {{ __('agricart.customer.returns.status_line', [
                            'number' => $pending->request_number,
                            'status' => __('agricart.customer.returns.statuses.'.$pending->status),
                        ]) }}
                    </p>
                    <form method="post" action="{{ route('store.customer.orders.return.cancel', $order->order_number) }}" onsubmit="return confirm(@json(__('agricart.customer.returns.cancel_confirm')))">
                        @csrf
                        <button type="submit" class="ag-store-btn ag-store-btn--secondary">{{ __('agricart.customer.returns.cancel') }}</button>
                    </form>
                </section>
            @elseif ($canSubmit)
                <form method="post" action="{{ route('store.customer.orders.return.store', $order->order_number) }}" enctype="multipart/form-data" class="ag-store-return">
                    @csrf
                    <ul class="ag-store-return__lines">
                        @foreach ($order->items as $item)
                            @php
                                $left = (float) ($remaining[$item->id] ?? 0);
                                $thumbUrl = $item->thumbnailUrl();
                            @endphp
                            <li class="ag-store-return__line">
                                <div class="ag-store-orders__line-thumb">
                                    @if ($thumbUrl)
                                        <img src="{{ $thumbUrl }}" alt="" class="ag-store-orders__line-img" width="56" height="56" />
                                    @else
                                        <span class="ag-store-orders__line-placeholder">{{ strtoupper(mb_substr($item->displayName(), 0, 1)) }}</span>
                                    @endif
                                </div>
                                <div class="ag-store-return__line-body">
                                    <strong class="ag-store-orders__line-name">{{ $item->displayName() }}</strong>
                                    <p class="ag-store-orders__line-meta">
                                        {{ __('agricart.customer.returns.remaining', ['qty' => $sales->formatQty($left)]) }}
                                    </p>
                                    @if ($left > 0)
                                        <div class="ag-store-return__fields">
                                            <label>
                                                <span>{{ __('agricart.customer.returns.quantity') }}</span>
                                                <input
                                                    type="number"
                                                    name="items[{{ $item->id }}][quantity]"
                                                    min="0"
                                                    max="{{ $left }}"
                                                    step="any"
                                                    value="{{ old('items.'.$item->id.'.quantity', '0') }}"
                                                />
                                            </label>
                                            <label>
                                                <span>{{ __('agricart.customer.returns.reason') }}</span>
                                                <select name="items[{{ $item->id }}][reason]">
                                                    <option value="">{{ __('agricart.customer.returns.reason_placeholder') }}</option>
                                                    @foreach ($reasons as $reason)
                                                        <option value="{{ $reason }}" @selected(old('items.'.$item->id.'.reason') === $reason)>
                                                            {{ __('agricart.customer.returns.reasons.'.$reason) }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </label>
                                            <label>
                                                <span>{{ __('agricart.customer.returns.photo') }}</span>
                                                <input type="file" name="items[{{ $item->id }}][photo]" accept="image/jpeg,image/png,image/webp" />
                                            </label>
                                        </div>
                                    @else
                                        <p class="ag-store-return__done">{{ __('agricart.customer.returns.none_left') }}</p>
                                    @endif
                                </div>
                            </li>
                        @endforeach
                    </ul>

                    <label class="ag-store-return__note">
                        <span>{{ __('agricart.customer.returns.note') }}</span>
                        <textarea name="customer_note" rows="3" maxlength="1000">{{ old('customer_note') }}</textarea>
                    </label>

                    <button type="submit" class="ag-store-btn ag-store-btn--primary">{{ __('agricart.customer.returns.submit') }}</button>
                </form>
            @else
                <p class="ag-store-orders__empty">{{ __('agricart.customer.returns.not_available') }}</p>
            @endif

            @if ($history !== [])
                <section class="ag-store-orders__detail-card">
                    <h2 class="ag-store-orders__section-title">{{ __('agricart.customer.returns.history') }}</h2>
                    <ul class="ag-store-return__history">
                        @foreach ($history as $entry)
                            <li>
                                <strong>{{ $entry->request_number }}</strong>
                                <span>{{ __('agricart.customer.returns.statuses.'.$entry->status) }}</span>
                                @if ($entry->status === 'rejected' && filled($entry->rejection_note))
                                    <span>{{ $entry->rejection_note }}</span>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endif
        </div>
    </div>
@endsection
