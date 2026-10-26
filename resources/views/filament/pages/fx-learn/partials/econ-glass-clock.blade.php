@php
    /** @var string $id tokyo|london|pk|ny */
@endphp

<article class="fx-radar__clock fx-radar__clock--{{ $id }}" aria-label="{{ __('agricart.pages.fx-learn.econ_clock_'.$id) }}">
    <div class="fx-radar__clock-case" aria-hidden="true">
        <div class="fx-radar__clock-bezel">
            <div class="fx-radar__clock-face">
                <span class="fx-radar__clock-flag"></span>
                <span class="fx-radar__clock-minutes"></span>
                <span class="fx-radar__clock-hours"></span>
                <span class="fx-radar__clock-hand fx-radar__clock-hand--hour" :style="`transform: rotate(${angles.{{ $id }}.h}deg)`"></span>
                <span class="fx-radar__clock-hand fx-radar__clock-hand--min" :style="`transform: rotate(${angles.{{ $id }}.m}deg)`"></span>
                <span class="fx-radar__clock-hand fx-radar__clock-hand--sec" :style="`transform: rotate(${angles.{{ $id }}.s}deg)`"></span>
                <span class="fx-radar__clock-pivot"></span>
            </div>
        </div>
    </div>

    <div class="fx-radar__clock-meta">
        <p class="fx-radar__clock-digital" dir="ltr">
            <span x-text="hm.{{ $id }}">--:--</span><span class="fx-radar__clock-sec" x-text="sec.{{ $id }}">:--</span>
        </p>
        <p class="fx-radar__clock-city">{{ __('agricart.pages.fx-learn.econ_clock_'.$id) }}</p>
        <p class="fx-radar__clock-tz" dir="ltr">{{ __('agricart.pages.fx-learn.econ_clock_tz_'.$id) }}</p>
    </div>
</article>
