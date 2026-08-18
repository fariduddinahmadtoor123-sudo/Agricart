<div class="agricart-categories agricart-rate-list">
    @if ($products->isEmpty())
        <div class="agricart-categories__empty">
            <p>{{ filled($this->search) ? __('rate_lists.list.no_results') : __('rate_lists.list.empty') }}</p>
        </div>
    @else
        <p class="agricart-rate-list__hint">{{ __('rate_lists.list.hint') }}</p>
        <div class="agricart-categories__table-wrap">
            <table class="agricart-categories__table">
                <thead>
                    <tr>
                        <th class="agricart-rate-list__sr">{{ __('rate_lists.columns.sr') }}</th>
                        @if ($bothNames)
                            <th>{{ __('rate_lists.columns.name_en') }}</th>
                            <th>{{ __('rate_lists.columns.name_ur') }}</th>
                        @else
                            <th>{{ __('rate_lists.columns.name') }}</th>
                        @endif
                        <th>{{ __('rate_lists.columns.rate') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($rows as $row)
                        <tr wire:key="rate-list-{{ $row['product_id'] }}" class="agricart-categories__row">
                            <td class="agricart-rate-list__sr">{{ $row['sr'] }}</td>
                            @if ($bothNames)
                                <td>{{ $row['name_en'] }}</td>
                                <td dir="rtl">{{ $row['name_ur'] }}</td>
                            @else
                                <td @if ($this->locale === 'ur') dir="rtl" @endif>{{ $row['name'] }}</td>
                            @endif
                            <td>{{ $row['rate'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="agricart-categories__pagination">
            {{ $products->links() }}
        </div>
    @endif
</div>

@script
<script>
    Livewire.on('rate-list-open-print', (payload) => {
        const url = payload?.url ?? payload?.[0]?.url;
        if (url) window.open(url, '_blank');
    });
</script>
@endscript
