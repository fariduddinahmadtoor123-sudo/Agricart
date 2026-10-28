<article @class(['fxr__clock', 'is-open' => $clock['open'], 'is-closed' => ! $clock['open']])>
    <div class="fxr__flag" aria-hidden="true">{{ $clock['flag'] }}</div>
    <div class="fxr__clock-meta">
        <p class="fxr__clock-city">{{ $clock['label'] }}</p>
        <p
            class="fxr__clock-time"
            dir="ltr"
            x-data="fxrSessionClock(@js([
                'tz' => $clock['tz'],
                'open' => $clock['open'],
                'boundaryMs' => $clock['boundary_ms'] ?? 0,
                'boundaryKind' => $clock['boundary_kind'] ?? 'open',
                'openStart' => $clock['open_start'] ?? 9,
                'openEnd' => $clock['open_end'] ?? 17,
                'untilLabel' => $clock['until_label'] ?? '',
                'hoursLabel' => $clock['hours_label'] ?? '',
                'labels' => [
                    'open' => __('agricart.pages.fx-learn.session_open'),
                    'closed' => __('agricart.pages.fx-learn.session_closed'),
                    'closesIn' => __('agricart.pages.fx-learn.session_closes_in', ['span' => '__SPAN__']),
                    'opensIn' => __('agricart.pages.fx-learn.session_opens_in', ['span' => '__SPAN__']),
                    'closesAt' => __('agricart.pages.fx-learn.session_closes_at', ['time' => '__TIME__']),
                    'opensAt' => __('agricart.pages.fx-learn.session_opens_at', ['time' => '__TIME__']),
                    'inHm' => __('agricart.pages.fx-learn.session_in_hm', ['h' => '__H__', 'm' => '__M__']),
                    'inM' => __('agricart.pages.fx-learn.session_in_m', ['m' => '__M__']),
                ],
            ]))"
            x-init="boot()"
            x-on:beforeunload.window="destroy()"
        >
            <span class="fxr__clock-hm" x-text="hm">{{ $clock['hm'] }}</span>
            <span class="fxr__clock-sec" x-text="sec">{{ $clock['sec'] }}</span>
        </p>
        <span
            class="fxr__session-pill"
            :class="{ 'is-on': open }"
            x-text="open ? labels.open : labels.closed"
        >{{ $clock['pill'] ?? '' }}</span>
        <p class="fxr__clock-until" dir="ltr" x-text="untilLabel">{{ $clock['until_label'] ?? '' }}</p>
        <p class="fxr__clock-hours" dir="ltr" x-text="hoursLabel">{{ $clock['hours_label'] ?? '' }}</p>
    </div>
</article>
