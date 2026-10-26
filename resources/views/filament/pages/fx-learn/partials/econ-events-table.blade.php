@php
    /** @var \Illuminate\Support\Collection<int, array<string, mixed>> $rows */
    $compact = $compact ?? false;
    $toolbar = $toolbar ?? false;
    $isLive = $isLive ?? false;
    $total = $total ?? $rows->count();
    $colspan = $toolbar ? 9 : 8;
@endphp

<table @class(['fx-radar__table', 'fx-radar__table--compact' => $compact, 'fx-radar__table--toolbar' => $toolbar])>
    <thead>
        <tr>
            <th>{{ __('agricart.pages.fx-learn.econ_col_time') }}</th>
            <th>{{ __('agricart.pages.fx-learn.econ_ccy') }}</th>
            <th>{{ __('agricart.pages.fx-learn.econ_impact') }}</th>
            <th>{{ __('agricart.pages.fx-learn.econ_col_event') }}</th>
            @if ($toolbar)
                <th class="fx-radar__th-tools" scope="col">
                    <div class="fx-radar__th-tools-inner">
                        <span
                            @class([
                                'fx-radar__live-dot',
                                'is-on' => $isLive,
                                'is-off' => ! $isLive,
                            ])
                            title="{{ $isLive ? __('agricart.pages.fx-learn.econ_feed_live') : __('agricart.pages.fx-learn.econ_feed_backup') }}"
                            aria-label="{{ $isLive ? __('agricart.pages.fx-learn.econ_feed_live') : __('agricart.pages.fx-learn.econ_feed_backup') }}"
                        ></span>

                        <button
                            type="button"
                            class="fx-radar__btn fx-radar__btn--view"
                            wire:click="openAllView"
                            @disabled($total === 0)
                        >{{ __('agricart.pages.fx-learn.econ_view_all') }}</button>

                        <button
                            type="button"
                            class="fx-radar__btn fx-radar__btn--refresh"
                            wire:click="refreshFeed"
                            wire:loading.attr="disabled"
                            title="{{ __('agricart.pages.fx-learn.econ_refresh') }}"
                        >{{ __('agricart.pages.fx-learn.econ_refresh') }}</button>
                    </div>
                </th>
            @endif
            <th>{{ __('agricart.pages.fx-learn.econ_actual') }}</th>
            <th>{{ __('agricart.pages.fx-learn.econ_forecast') }}</th>
            <th>{{ __('agricart.pages.fx-learn.econ_previous') }}</th>
            <th>{{ __('agricart.pages.fx-learn.econ_col_status') }}</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($rows as $ev)
            @php $status = $this->eventStatus($ev); @endphp
            <tr @class(['is-live' => $status === 'live'])>
                <td class="fx-radar__td-time" dir="ltr">
                    <span>{{ $this->formatPkt((int) $ev['at']) }}</span>
                    <small>{{ $this->countdownLabel((int) $ev['at']) }}</small>
                </td>
                <td class="fx-radar__td-ccy" dir="ltr">{{ $ev['ccy'] }}</td>
                <td>
                    <span
                        @class(['fx-radar__stars', 'fx-radar__stars--'.$ev['impact']])
                        title="{{ __('agricart.pages.fx-learn.econ_impact_'.$ev['impact']) }}"
                        aria-label="{{ __('agricart.pages.fx-learn.econ_impact_'.$ev['impact']) }}"
                    >{{ $this->impactStars((string) $ev['impact']) }}</span>
                </td>
                <td class="fx-radar__td-event">{{ $ev['title'] }}</td>
                @if ($toolbar)
                    <td class="fx-radar__td-tools" aria-hidden="true"></td>
                @endif
                <td class="fx-radar__td-num" dir="ltr">{{ $ev['actual'] }}</td>
                <td class="fx-radar__td-num" dir="ltr">{{ $ev['forecast'] }}</td>
                <td class="fx-radar__td-num" dir="ltr">{{ $ev['previous'] }}</td>
                <td>
                    <span @class(['fx-radar__status', 'fx-radar__status--'.$status])>
                        {{ __('agricart.pages.fx-learn.econ_status_'.$status) }}
                    </span>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="{{ $colspan }}" class="fx-radar__empty">{{ __('agricart.pages.fx-learn.econ_empty') }}</td>
            </tr>
        @endforelse
    </tbody>
</table>
