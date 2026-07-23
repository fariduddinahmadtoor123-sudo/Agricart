@props([
    'linkableType',
    'linkableId',
    'threadProfile' => null,
    'defaultTab' => null,
])

@php
    $defaultTab = $defaultTab ?: ($threadProfile ? 'thread' : 'notes');
@endphp

@if ($linkableId)
    <div
        class="agricart-comms-record-panels"
        x-data="{ tab: @js($defaultTab) }"
        {{ $attributes }}
    >
        <div class="agricart-comms-tabs" role="tablist">
            @if (filled($threadProfile))
                <button type="button" class="agricart-comms-tabs__btn" role="tab" :aria-selected="tab === 'thread'" :class="{ 'is-active': tab === 'thread' }" @click="tab = 'thread'">
                    {{ __('communication.tabs.messages') }}
                </button>
            @endif
            <button type="button" class="agricart-comms-tabs__btn" role="tab" :aria-selected="tab === 'notes'" :class="{ 'is-active': tab === 'notes' }" @click="tab = 'notes'">
                {{ __('communication.tabs.notes') }}
            </button>
            <button type="button" class="agricart-comms-tabs__btn" role="tab" :aria-selected="tab === 'tasks'" :class="{ 'is-active': tab === 'tasks' }" @click="tab = 'tasks'">
                {{ __('communication.tabs.tasks') }}
            </button>
        </div>

        @if (filled($threadProfile))
            <div x-show="tab === 'thread'" x-cloak>
                <livewire:communication.record-channel-thread-panel
                    :profile-key="$threadProfile"
                    :linkable-id="(int) $linkableId"
                    :key="'thread-'.$threadProfile.'-'.$linkableId"
                />
            </div>
        @endif

        <div x-show="tab === 'notes'" x-cloak>
            <livewire:communication.internal-notes-panel
                :linkable-type="$linkableType"
                :linkable-id="(int) $linkableId"
                :key="'notes-'.$linkableType.'-'.$linkableId"
            />
        </div>

        <div x-show="tab === 'tasks'" x-cloak>
            <livewire:communication.task-communication-panel
                :linkable-type="$linkableType"
                :linkable-id="(int) $linkableId"
                :key="'tasks-'.$linkableType.'-'.$linkableId"
            />
        </div>
    </div>
@endif
