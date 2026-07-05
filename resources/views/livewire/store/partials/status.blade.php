@if ($statusMessage)
    <div @class(['agricart-store-admin__alert', 'is-success' => $statusType === 'success', 'is-danger' => $statusType === 'danger'])>
        {{ $statusMessage }}
    </div>
@endif
