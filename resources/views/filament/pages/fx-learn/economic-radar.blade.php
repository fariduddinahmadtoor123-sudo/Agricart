@php
    $visible = $this->filteredEvents();
    $selected = $this->selectedEvent();
@endphp

<x-filament-panels::page>
    <div class="fx-econ">
        <header class="fx-econ__head no-print">
            <div class="fx-econ__head-text">
                <h1 class="fx-econ__title">{{ __('agricart.pages.fx-learn.economic-radar') }}</h1>
                <p class="fx-econ__intro">{{ __('agricart.pages.fx-learn.econ_page_intro') }}</p>
            </div>
            <div class="fx-econ__head-meta">
                <span @class([
                    'fx-econ__badge',
                    'fx-econ__badge--live' => in_array($this->feedSource, ['ff+fc', 'ff', 'fc'], true),
                    'fx-econ__badge--backup' => $this->feedSource === 'backup',
                ])>{{ $this->feedBadgeLabel() }}</span>
                <span class="fx-econ__zone">{{ __('agricart.pages.fx-learn.econ_times_pkt') }}</span>
                <button
                    type="button"
                    class="fx-econ__refresh"
                    wire:click="refreshFeed"
                    wire:loading.attr="disabled"
                >{{ __('agricart.pages.fx-learn.econ_refresh') }}</button>
                <button
                    type="button"
                    class="fx-econ__refresh fx-econ__print-btn"
                    wire:click="printFiltered"
                    @disabled($visible->isEmpty())
                >{{ __('agricart.pages.fx-learn.econ_print') }}</button>
            </div>
        </header>

        <div class="fx-econ__filters no-print" role="toolbar" aria-label="{{ __('agricart.pages.fx-learn.econ_filters') }}">
            <div class="fx-econ__filter-row">
                <span class="fx-econ__filter-label">{{ __('agricart.pages.fx-learn.econ_tf') }}</span>
                <div class="fx-econ__chips">
                    @foreach ([
                        'lastweek' => __('agricart.pages.fx-learn.econ_tf_last'),
                        'today' => __('agricart.pages.fx-learn.econ_tf_today'),
                        'tomorrow' => __('agricart.pages.fx-learn.econ_tf_tomorrow'),
                        'nextweek' => __('agricart.pages.fx-learn.econ_tf_next'),
                    ] as $id => $label)
                        <button
                            type="button"
                            wire:click="setTf('{{ $id }}')"
                            @class(['fx-econ__chip', 'is-active' => $this->tf === $id])
                        >{{ $label }}</button>
                    @endforeach
                </div>
            </div>
            <div class="fx-econ__filter-row">
                <span class="fx-econ__filter-label">{{ __('agricart.pages.fx-learn.econ_impact') }}</span>
                <div class="fx-econ__chips">
                    <button type="button" wire:click="setImpact('all')" @class(['fx-econ__chip', 'is-active' => $this->impact === 'all'])>{{ __('agricart.pages.fx-learn.econ_impact_all') }}</button>
                    <button type="button" wire:click="setImpact('high')" @class(['fx-econ__chip', 'is-active' => $this->impact === 'high'])>{{ __('agricart.pages.fx-learn.econ_impact_high') }}</button>
                </div>
            </div>
            <div class="fx-econ__filter-row">
                <span class="fx-econ__filter-label">{{ __('agricart.pages.fx-learn.econ_ccy') }}</span>
                <div class="fx-econ__chips">
                    @foreach (['ALL', 'USD', 'EUR', 'GBP', 'JPY'] as $code)
                        <button
                            type="button"
                            wire:click="setCcy('{{ $code }}')"
                            @class(['fx-econ__chip', 'is-active' => $this->ccy === $code])
                        >{{ $code === 'ALL' ? __('agricart.pages.fx-learn.econ_ccy_all') : $code }}</button>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="fx-econ__layout no-print">
            <section class="fx-econ__focus" aria-live="polite">
                @if ($selected)
                    @php $tone = $this->actualTone($selected); @endphp
                    <div class="fx-econ__focus-card">
                        <div class="fx-econ__focus-top">
                            <span @class(['fx-econ__impact', 'fx-econ__impact--'.$selected['impact']])>
                                {{ __('agricart.pages.fx-learn.econ_impact_'.$selected['impact']) }}
                            </span>
                            <span class="fx-econ__ccy-pill">{{ $selected['ccy'] }}</span>
                            <span class="fx-econ__countdown">{{ $this->countdownLabel((int) $selected['at']) }}</span>
                        </div>
                        <h2 class="fx-econ__focus-title">{{ $selected['title'] }}</h2>
                        <p class="fx-econ__focus-meta">
                            {{ $selected['country'] }}
                            ·
                            <time datetime="{{ \Carbon\CarbonImmutable::createFromTimestampMs((int) $selected['at'], 'UTC')->toIso8601String() }}">
                                {{ $this->formatPkt((int) $selected['at']) }}
                            </time>
                        </p>
                        <div class="fx-econ__stats">
                            <div class="fx-econ__stat">
                                <span class="fx-econ__stat-k">{{ __('agricart.pages.fx-learn.econ_actual') }}</span>
                                <span @class(['fx-econ__stat-v', 'is-'.$tone => $tone !== ''])>{{ $selected['actual'] }}</span>
                            </div>
                            <div class="fx-econ__stat">
                                <span class="fx-econ__stat-k">{{ __('agricart.pages.fx-learn.econ_forecast') }}</span>
                                <span class="fx-econ__stat-v">{{ $selected['forecast'] }}</span>
                            </div>
                            <div class="fx-econ__stat">
                                <span class="fx-econ__stat-k">{{ __('agricart.pages.fx-learn.econ_previous') }}</span>
                                <span class="fx-econ__stat-v">{{ $selected['previous'] }}</span>
                            </div>
                        </div>
                    </div>
                @else
                    <div class="fx-econ__empty">{{ __('agricart.pages.fx-learn.econ_empty') }}</div>
                @endif
            </section>

            <section class="fx-econ__list-wrap">
                <div class="fx-econ__list-head">
                    <h3 class="fx-econ__list-title">{{ __('agricart.pages.fx-learn.econ_list_title') }}</h3>
                    <span class="fx-econ__list-count">{{ $visible->count() }}</span>
                </div>
                <div class="fx-econ__list" role="list">
                    @forelse ($visible as $ev)
                        <button
                            type="button"
                            role="listitem"
                            wire:click="selectEvent('{{ $ev['id'] }}')"
                            @class([
                                'fx-econ__row',
                                'is-selected' => $selected && $selected['id'] === $ev['id'],
                            ])
                        >
                            <div class="fx-econ__row-main">
                                <span @class(['fx-econ__dot', 'fx-econ__dot--'.$ev['impact']])></span>
                                <span class="fx-econ__row-ccy">{{ $ev['ccy'] }}</span>
                                <span class="fx-econ__row-title">{{ $ev['title'] }}</span>
                            </div>
                            <div class="fx-econ__row-side">
                                <span class="fx-econ__row-time">{{ $this->formatPkt((int) $ev['at']) }}</span>
                                <span class="fx-econ__row-eta">{{ $this->countdownLabel((int) $ev['at']) }}</span>
                            </div>
                        </button>
                    @empty
                        <p class="fx-econ__empty">{{ __('agricart.pages.fx-learn.econ_empty') }}</p>
                    @endforelse
                </div>
            </section>
        </div>

        <div class="fx-econ__print-root" aria-hidden="true">
            <header class="fx-econ__print-head">
                <h1>{{ __('agricart.pages.fx-learn.economic-radar') }}</h1>
                <p>{{ $this->filterSummary() }}</p>
                <p class="fx-econ__print-meta">
                    {{ __('agricart.pages.fx-learn.econ_times_pkt') }}
                    ·
                    {{ __('agricart.pages.fx-learn.econ_print_count', ['count' => $visible->count()]) }}
                    ·
                    {{ agricart_datetime(now('UTC'), 'Y-m-d H:i', true) }}
                </p>
            </header>

            @if ($visible->isEmpty())
                <p>{{ __('agricart.pages.fx-learn.econ_empty') }}</p>
            @else
                <table class="fx-econ__print-table">
                    <thead>
                        <tr>
                            <th>{{ __('agricart.pages.fx-learn.econ_print_col_time') }}</th>
                            <th>{{ __('agricart.pages.fx-learn.econ_ccy') }}</th>
                            <th>{{ __('agricart.pages.fx-learn.econ_impact') }}</th>
                            <th>{{ __('agricart.pages.fx-learn.econ_print_col_event') }}</th>
                            <th>{{ __('agricart.pages.fx-learn.econ_actual') }}</th>
                            <th>{{ __('agricart.pages.fx-learn.econ_forecast') }}</th>
                            <th>{{ __('agricart.pages.fx-learn.econ_previous') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($visible as $ev)
                            <tr>
                                <td>{{ $this->formatPkt((int) $ev['at']) }}</td>
                                <td>{{ $ev['ccy'] }}</td>
                                <td>{{ __('agricart.pages.fx-learn.econ_impact_'.$ev['impact']) }}</td>
                                <td>
                                    <strong>{{ $ev['title'] }}</strong>
                                    <span class="fx-econ__print-country">{{ $ev['country'] }}</span>
                                </td>
                                <td>{{ $ev['actual'] }}</td>
                                <td>{{ $ev['forecast'] }}</td>
                                <td>{{ $ev['previous'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </div>
</x-filament-panels::page>
