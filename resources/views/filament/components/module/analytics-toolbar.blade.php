<div
    class="agricart-analytics-toolbar"
    x-data="{
        dateFrom: @js($toolbar['dateFrom']),
        dateTo: @js($toolbar['dateTo']),
        country: '',
        city: '',
        device: '',
        source: '',
        apply() {
            window.Livewire?.dispatchTo('store.visitor-analytics-dashboard', 'applyAnalyticsFilters', {
                dateFrom: this.dateFrom,
                dateTo: this.dateTo,
                country: this.country,
                city: this.city,
                device: this.device,
                source: this.source,
            });
        },
        clear() {
            this.dateFrom = @js($toolbar['dateFrom']);
            this.dateTo = @js($toolbar['dateTo']);
            this.country = '';
            this.city = '';
            this.device = '';
            this.source = '';
            this.apply();
        },
    }"
>
    <label>
        <span>{{ __('agricart.analytics.from') }}</span>
        <input type="date" x-model="dateFrom" x-on:change="apply()" />
    </label>
    <label>
        <span>{{ __('agricart.analytics.to') }}</span>
        <input type="date" x-model="dateTo" x-on:change="apply()" />
    </label>
    <label>
        <span>{{ __('agricart.analytics.country') }}</span>
        <select x-model="country" x-on:change="apply()">
            <option value="">{{ __('agricart.analytics.all') }}</option>
            @foreach ($toolbar['analyticsOptions']['countries'] as $option)
                <option value="{{ $option }}">{{ $option }}</option>
            @endforeach
        </select>
    </label>
    <label>
        <span>{{ __('agricart.analytics.city') }}</span>
        <select x-model="city" x-on:change="apply()">
            <option value="">{{ __('agricart.analytics.all') }}</option>
            @foreach ($toolbar['analyticsOptions']['cities'] as $option)
                <option value="{{ $option }}">{{ $option }}</option>
            @endforeach
        </select>
    </label>
    <label>
        <span>{{ __('agricart.analytics.device') }}</span>
        <select x-model="device" x-on:change="apply()">
            <option value="">{{ __('agricart.analytics.all') }}</option>
            @foreach ($toolbar['analyticsOptions']['devices'] as $option)
                <option value="{{ $option }}">{{ ucfirst($option) }}</option>
            @endforeach
        </select>
    </label>
    <label>
        <span>{{ __('agricart.analytics.source') }}</span>
        <select x-model="source" x-on:change="apply()">
            <option value="">{{ __('agricart.analytics.all') }}</option>
            @foreach ($toolbar['analyticsOptions']['sources'] as $option)
                <option value="{{ $option }}">{{ ucfirst($option) }}</option>
            @endforeach
        </select>
    </label>
    <button type="button" x-on:click="clear()">{{ __('agricart.analytics.clear') }}</button>
</div>
