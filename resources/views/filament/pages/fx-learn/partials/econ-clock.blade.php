<article
    @class(['fxr__clock', 'is-open' => $clock['open'], 'is-closed' => ! $clock['open']])
    x-data="fxrSessionClock(@js([
        'tz' => $clock['tz'],
        'open' => $clock['open'],
        'closeBoundaryMs' => $clock['close_boundary_ms'] ?? 0,
        'openBoundaryMs' => $clock['open_boundary_ms'] ?? 0,
        'openStart' => $clock['open_start'] ?? 9,
        'openEnd' => $clock['open_end'] ?? 17,
        'openUntilLabel' => $clock['open_until_label'] ?? '',
        'closeUntilLabel' => $clock['close_until_label'] ?? '',
        'labels' => [
            'open' => __('agricart.pages.fx-learn.session_open'),
            'opensIn' => __('agricart.pages.fx-learn.session_opens_in', ['span' => '__SPAN__']),
            'closesIn' => __('agricart.pages.fx-learn.session_closes_in', ['span' => '__SPAN__']),
        ],
    ]))"
    x-init="boot()"
    x-on:beforeunload.window="destroy()"
    :class="{ 'is-open': open, 'is-closed': !open }"
>
    <div class="fxr__flag" aria-hidden="true">{{ $clock['flag'] }}</div>
    <div class="fxr__clock-meta">
        <p class="fxr__clock-city">{{ $clock['label'] }}</p>
        <p class="fxr__clock-until is-opening" dir="ltr" x-text="openUntilLabel">{{ $clock['open_until_label'] ?? '' }}</p>
        <p class="fxr__clock-time" dir="ltr">
            <span class="fxr__clock-hm" x-text="hm">{{ $clock['hm'] }}</span>
            <span class="fxr__clock-sec" x-text="sec">{{ $clock['sec'] }}</span>
        </p>
        <p class="fxr__clock-until is-closing" dir="ltr" x-text="closeUntilLabel">{{ $clock['close_until_label'] ?? '' }}</p>
    </div>
</article>
