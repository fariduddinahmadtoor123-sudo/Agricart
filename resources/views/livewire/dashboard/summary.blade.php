<div class="agricart-categories agricart-dashboard-summary">
    <div class="agricart-categories__empty" style="margin-bottom: 1rem;">
        <p>{{ __('agricart.dashboard.summary.generated', ['time' => $generatedAt]) }}</p>
    </div>

    <div class="agricart-categories__table-wrap">
        <table class="agricart-categories__table">
            <thead>
                <tr>
                    <th>{{ __('agricart.dashboard.summary.columns.metric') }}</th>
                    <th>{{ __('agricart.dashboard.summary.columns.today') }}</th>
                    <th>{{ __('agricart.dashboard.summary.columns.month') }}</th>
                    <th class="is-actions">{{ __('agricart.dashboard.summary.columns.action') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($rows as $row)
                    <tr wire:key="dashboard-summary-{{ $row['key'] }}" class="agricart-categories__row">
                        <td><strong>{{ $row['label'] }}</strong></td>
                        <td>{{ $row['today'] }}</td>
                        <td>{{ $row['month'] }}</td>
                        <td class="agricart-categories__actions">
                            <a href="{{ $row['url'] }}" class="agricart-categories__action">
                                {{ __('agricart.dashboard.summary.open') }}
                            </a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
