@php
    $segments = $toolbar['segments'] ?? [];
@endphp

<div class="agricart-toolbar-segments" role="tablist" aria-label="{{ $toolbar['resourceLabel'] ?? '' }}">
    @foreach ($segments as $segment)
        <button
            type="button"
            role="tab"
            class="agricart-toolbar-segments__btn"
            x-bind:class="{ 'is-active': activeTab === @js($segment['value']) }"
            x-bind:aria-selected="(activeTab === @js($segment['value'])).toString()"
            x-on:click="switchTab(@js($segment['value']))"
        >
            <span>{{ $segment['label'] }}</span>
            @if ($segment['badge'] ?? false)
                <span
                    class="agricart-toolbar-segments__badge"
                    x-show="queueCount > 0"
                    x-text="queueCount"
                    x-cloak
                ></span>
            @endif
        </button>
    @endforeach
</div>
