@php
    $types = $this->types();
    $canEdit = $this->canEditTypes();
@endphp

<x-filament-panels::page>
    <div class="fxt fxt--atypes">
        <header class="fxt__atypes-intro">
            <div>
                <p class="fxt__kicker">{{ __('agricart.pages.fx-learn.atype_kicker') }}</p>
                <h1 class="fxt__atypes-title">{{ __('agricart.pages.fx-learn.atype_title') }}</h1>
                <p class="fxt__atypes-lead">{{ __('agricart.pages.fx-learn.atype_lead') }}</p>
            </div>
            @if ($canEdit)
                <button type="button" class="fxt__btn fxt__btn--primary" wire:click="startCreate">
                    {{ __('agricart.pages.fx-learn.atype_add') }}
                </button>
            @endif
        </header>

        <div class="fxt__atypes-grid">
            @forelse ($types as $type)
                <article @class(['fxt__atype-card', 'is-off' => ! $type->is_active]) wire:key="atype-{{ $type->id }}">
                    <div class="fxt__atype-card-top">
                        <div>
                            <h2 class="fxt__atype-name">{{ $type->name }}</h2>
                            <p class="fxt__atype-balance" dir="ltr">${{ number_format((float) $type->starting_balance, 0) }}</p>
                        </div>
                        <span @class(['fxt__atype-badge', 'is-on' => $type->is_active])>
                            {{ $type->is_active ? __('agricart.pages.fx-learn.atype_active') : __('agricart.pages.fx-learn.atype_inactive') }}
                        </span>
                    </div>
                    @if (filled($type->description))
                        <p class="fxt__atype-desc">{{ $type->description }}</p>
                    @endif
                    <dl class="fxt__atype-rules">
                        <div><dt>{{ __('agricart.pages.fx-learn.prop_phase1') }}</dt><dd dir="ltr">{{ number_format((float) $type->phase1_pct, 1) }}%</dd></div>
                        <div><dt>{{ __('agricart.pages.fx-learn.prop_phase2') }}</dt><dd dir="ltr">{{ number_format((float) $type->phase2_pct, 1) }}%</dd></div>
                        <div><dt>{{ __('agricart.pages.fx-learn.prop_phase3') }}</dt><dd dir="ltr">{{ number_format((float) $type->phase3_pct, 1) }}%</dd></div>
                        <div><dt>{{ __('agricart.pages.fx-learn.prop_max_day') }}</dt><dd dir="ltr">{{ number_format((float) $type->max_day_loss_pct, 1) }}%</dd></div>
                        <div><dt>{{ __('agricart.pages.fx-learn.prop_max_total') }}</dt><dd dir="ltr">{{ number_format((float) $type->max_total_loss_pct, 1) }}%</dd></div>
                        <div><dt>{{ __('agricart.pages.fx-learn.prop_max_trade') }}</dt><dd dir="ltr">{{ number_format((float) $type->max_trade_loss_pct, 1) }}%</dd></div>
                    </dl>
                    @if ($canEdit)
                        <div class="fxt__atype-actions">
                            <button type="button" class="fxt__mini" wire:click="startEdit({{ $type->id }})">{{ __('agricart.pages.fx-learn.atype_edit') }}</button>
                            <button type="button" class="fxt__mini" wire:click="toggleActive({{ $type->id }})">
                                {{ $type->is_active ? __('agricart.pages.fx-learn.atype_deactivate') : __('agricart.pages.fx-learn.atype_activate') }}
                            </button>
                            <button
                                type="button"
                                class="fxt__mini fxt__mini--danger"
                                wire:click="deleteType({{ $type->id }})"
                                wire:confirm="{{ __('agricart.pages.fx-learn.atype_delete_confirm') }}"
                            >{{ __('agricart.pages.fx-learn.atype_delete') }}</button>
                        </div>
                    @endif
                </article>
            @empty
                <p class="fxt__empty">{{ __('agricart.pages.fx-learn.atype_empty') }}</p>
            @endforelse
        </div>

        @if ($showForm && $canEdit)
            <div class="fxt__modal" role="dialog" aria-modal="true">
                <div class="fxt__modal-backdrop" wire:click="cancelForm"></div>
                <div class="fxt__modal-card fxt__modal-card--wide">
                    <div class="fxt__modal-head">
                        <h2>{{ $editingId ? __('agricart.pages.fx-learn.atype_edit_title') : __('agricart.pages.fx-learn.atype_add_title') }}</h2>
                        <button type="button" wire:click="cancelForm">×</button>
                    </div>
                    <p class="fxt__modal-hint">{{ __('agricart.pages.fx-learn.atype_form_hint') }}</p>
                    <div class="fxt__settings-grid">
                        <label><span>{{ __('agricart.pages.fx-learn.atype_field_name') }}</span><input type="text" wire:model="name" /></label>
                        <label><span>{{ __('agricart.pages.fx-learn.atype_field_slug') }}</span><input type="text" wire:model="typeSlug" dir="ltr" placeholder="auto" /></label>
                        <label class="fxt__span-2"><span>{{ __('agricart.pages.fx-learn.atype_field_desc') }}</span><input type="text" wire:model="description" /></label>
                        <label><span>{{ __('agricart.pages.fx-learn.prop_balance') }}</span><input type="number" step="1" min="100" wire:model="startingBalance" /></label>
                        <label><span>{{ __('agricart.pages.fx-learn.atype_field_sort') }}</span><input type="number" step="1" min="0" wire:model="sortOrder" /></label>
                        <label><span>{{ __('agricart.pages.fx-learn.prop_phase1') }}</span><input type="number" step="0.1" wire:model="phase1Pct" /></label>
                        <label><span>{{ __('agricart.pages.fx-learn.prop_phase2') }}</span><input type="number" step="0.1" wire:model="phase2Pct" /></label>
                        <label><span>{{ __('agricart.pages.fx-learn.prop_phase3') }}</span><input type="number" step="0.1" wire:model="phase3Pct" /></label>
                        <label><span>{{ __('agricart.pages.fx-learn.prop_max_day') }}</span><input type="number" step="0.1" wire:model="maxDayLossPct" /></label>
                        <label><span>{{ __('agricart.pages.fx-learn.prop_max_total') }}</span><input type="number" step="0.1" wire:model="maxTotalLossPct" /></label>
                        <label><span>{{ __('agricart.pages.fx-learn.prop_max_trade') }}</span><input type="number" step="0.1" wire:model="maxTradeLossPct" /></label>
                        <label class="fxt__check">
                            <input type="checkbox" wire:model="isActive" />
                            <span>{{ __('agricart.pages.fx-learn.atype_active') }}</span>
                        </label>
                    </div>
                    <div class="fxt__modal-foot">
                        <button type="button" class="fxt__btn" wire:click="cancelForm">{{ __('agricart.pages.fx-learn.atype_cancel') }}</button>
                        <button type="button" class="fxt__btn fxt__btn--primary" wire:click="saveType">{{ __('agricart.pages.fx-learn.atype_save') }}</button>
                    </div>
                </div>
            </div>
        @endif
    </div>
</x-filament-panels::page>
