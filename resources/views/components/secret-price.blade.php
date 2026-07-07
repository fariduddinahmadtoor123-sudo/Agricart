@props(['value' => ''])

@if (filled($value))
    {{ app(\App\Services\PricingControls\SecretCodingEngine::class)->encodePrice((string) $value) }}
@else
    —
@endif
