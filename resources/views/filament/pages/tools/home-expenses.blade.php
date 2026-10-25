<x-filament-panels::page>
    <style>
        .agricart-catalog-toolbar--tools-he{flex-wrap:wrap;gap:.5rem .75rem}
        .agricart-catalog-toolbar--tools-he .agricart-catalog-toolbar__search{flex:0 1 9.5rem;max-width:10.5rem;min-width:7rem}
        .agricart-catalog-toolbar--tools-he .agricart-catalog-toolbar__search .fi-input-wrp{width:100%;max-width:10.5rem}
        .agricart-he-toolbar-slot{flex:1 1 14rem;min-width:12rem;display:flex;align-items:center}
        .agricart-catalog-toolbar--tools-he .agricart-catalog-toolbar__actions{flex:0 0 auto;flex-wrap:wrap;justify-content:flex-end;gap:.35rem}
        .agricart-he__totals{
            display:flex;flex-wrap:wrap;gap:.55rem .85rem;align-items:center;
            font-size:.8125rem;font-weight:700;color:#0f172a;width:100%
        }
        .agricart-he__totals span{direction:ltr;white-space:nowrap}
        .agricart-he__totals-muted{color:#64748b;font-weight:600}
        .agricart-he__totals-remaining{color:#b91c1c}
        .agricart-he{display:flex;flex-direction:column;gap:1rem}
        .agricart-he__hint{margin:0;font-size:.8125rem;color:#64748b;line-height:1.45}
        .agricart-he__sheet-title{margin:0;font-size:.9375rem;font-weight:800;color:#0f172a}
        .agricart-he__head{display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:.5rem}
        .agricart-ro-list{display:flex;flex-direction:column;gap:.75rem}
        .agricart-ro-list__table-wrap{overflow:auto;border-radius:.75rem;background:#fff;border:1px solid rgba(15,23,42,.08)}
        .agricart-ro-list__table{width:100%;border-collapse:collapse;font-size:.8125rem}
        .agricart-ro-list__table th{text-align:start;padding:.65rem .85rem;font-size:.6875rem;font-weight:700;text-transform:uppercase;color:#64748b;border-bottom:1px solid rgba(15,23,42,.08);background:rgba(148,163,184,.08)}
        .agricart-ro-list__table td{padding:.7rem .85rem;border-bottom:1px solid rgba(15,23,42,.06);vertical-align:middle}
        .agricart-ro-list__order{font-weight:700}
        .agricart-ro-list__actions{display:flex;gap:.35rem;justify-content:flex-end;flex-wrap:wrap}
        .agricart-ro-list__empty{padding:1.5rem;text-align:center;color:#64748b}
        .agricart-he__badge{
            display:inline-flex;align-items:center;padding:.15rem .45rem;border-radius:.4rem;
            font-size:.68rem;font-weight:800;text-transform:uppercase
        }
        .agricart-he__badge--paid{background:#dcfce7;color:#166534}
        .agricart-he__badge--unpaid{background:#fee2e2;color:#b91c1c}
        .agricart-he__badge--open{background:#e0f2fe;color:#075985}
        .agricart-he__badge--closed{background:#f1f5f9;color:#475569}
        .agricart-he__remain-box{
            border-radius:.75rem;background:#fef2f2;border:1px solid rgba(185,28,28,.18);
            padding:.75rem .9rem;display:flex;flex-direction:column;gap:.45rem
        }
        .agricart-he__remain-title{margin:0;font-size:.8125rem;font-weight:800;color:#b91c1c}
        .agricart-he__paid-list{display:flex;flex-wrap:wrap;gap:.4rem .65rem;margin:0;padding:0;list-style:none}
        .agricart-he__remain-list li{
            font-size:.8125rem;font-weight:600;color:#7f1d1d;
            background:#fff;border:1px solid rgba(185,28,28,.2);border-radius:.45rem;padding:.25rem .55rem
        }
        .agricart-he__remain-list li span{direction:ltr;font-weight:800}
        .agricart-he__paid-empty{margin:0;font-size:.8125rem;color:#64748b}
        .agricart-he__folders{display:flex;flex-direction:column;gap:.55rem}
        .agricart-he__folders-title{margin:0;font-size:.875rem;font-weight:800;color:#0f172a}
        .agricart-he__folder-ico{display:inline-flex;align-items:center;gap:.45rem}
        .agricart-he__folder-ico svg{width:1.15rem;height:1.15rem;color:#ca8a04;flex:0 0 auto}
        .agricart-he__modal{
            position:fixed;inset:0;z-index:200;display:flex;align-items:center;justify-content:center;padding:1rem
        }
        .agricart-he__modal-backdrop{position:absolute;inset:0;z-index:0;background:rgba(15,23,42,.45);backdrop-filter:blur(2px)}
        .agricart-he__modal-card{
            position:relative;z-index:1;width:min(100%,24rem);max-height:min(92vh,34rem);
            display:flex;flex-direction:column;border-radius:1rem;background:#fff;
            box-shadow:0 20px 50px rgba(15,23,42,.28);overflow:hidden
        }
        .agricart-he__modal-head{
            display:flex;align-items:center;justify-content:space-between;gap:.5rem;
            padding:.85rem 1rem;border-bottom:1px solid rgba(15,23,42,.08)
        }
        .agricart-he__modal-title{margin:0;font-size:.9375rem;font-weight:800;color:#0f172a}
        .agricart-he__modal-close{
            appearance:none;border:0;background:rgba(148,163,184,.15);color:#475569;
            width:1.75rem;height:1.75rem;border-radius:.5rem;cursor:pointer;font-size:1.1rem;font-weight:800;line-height:1
        }
        .agricart-he__modal-body{padding:1rem;display:flex;flex-direction:column;gap:.75rem;overflow:auto}
        .agricart-he__field{display:flex;flex-direction:column;gap:.22rem}
        .agricart-he__field span{font-size:.68rem;font-weight:700;color:#64748b;text-transform:uppercase}
        .agricart-he__field input{
            border:1px solid #cbd5e1;border-radius:.45rem;padding:.5rem .55rem;font-size:.875rem;width:100%
        }
        .agricart-he__modal-foot{
            padding:.75rem 1rem;border-top:1px solid rgba(15,23,42,.08);
            display:flex;justify-content:flex-end;gap:.5rem;background:#f8fafc
        }
        .agricart-he__btn{
            appearance:none;border:1px solid #cbd5e1;background:#fff;color:#0f172a;
            font-size:.8125rem;font-weight:700;padding:.45rem .9rem;border-radius:.55rem;cursor:pointer
        }
        .agricart-he__btn--primary{background:#0f172a;border-color:#0f172a;color:#fff}
    </style>

    @php
        $viewingFolder = $this->isViewingFolder();
        $items = $this->listedItems();
        $totalsSource = $this->sheetItemsForTotals();
        $unpaidItems = $totalsSource->where('is_paid', false)->values();
        $totals = $this->sheetTotals($totalsSource);
        $folders = $viewingFolder ? collect() : $this->monthFolders();
        $money = static fn ($n) => 'Rs '.number_format((float) $n, 0, '.', ',');
        $totalsPayload = [
            'title' => $this->activeSheetTitle(),
            'expected' => $money($totals['expected']),
            'paid' => $money($totals['paid']),
            'remaining' => $money($totals['unpaid']),
            'viewingClosed' => $viewingFolder,
        ];
    @endphp

    <div
        id="agricart-he-totals-data"
        hidden
        data-payload='@json($totalsPayload)'
        wire:ignore.self
    ></div>

    <div
        class="agricart-he"
        wire:key="he-page-{{ $openSheetId }}-{{ $viewingFolderId ?? 0 }}-{{ $showAddForm ? 'form' : ($payingItemId ? 'pay' : 'list') }}-{{ $totals['paid_count'] }}"
        x-data
        x-init="
            const push = () => window.dispatchEvent(new CustomEvent('tools-home-expenses-totals', { detail: @js($totalsPayload) }));
            push();
            $nextTick(push);
        "
    >
        <div class="agricart-ro-list">
            <div class="agricart-he__head">
                <h2 class="agricart-he__sheet-title">
                    @if ($viewingFolder)
                        <span class="agricart-he__folder-ico">
                            <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M10 4H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V8a2 2 0 0 0-2-2h-8l-2-2z"/></svg>
                            {{ $this->activeSheetTitle() }}
                        </span>
                    @else
                        {{ $this->activeSheetTitle() }}
                    @endif
                </h2>
                @if ($viewingFolder)
                    <x-filament::button size="sm" color="primary" wire:click="backToOpenList">
                        {{ __('agricart.pages.tools.he_back_list') }}
                    </x-filament::button>
                @endif
            </div>

            <p class="agricart-he__hint">
                @if ($viewingFolder)
                    {{ __('agricart.pages.tools.he_folder_hint') }}
                @else
                    {{ __('agricart.pages.tools.he_list_hint') }}
                @endif
            </p>

            @unless ($viewingFolder)
                <div class="agricart-he__remain-box">
                    <h3 class="agricart-he__remain-title">
                        {{ __('agricart.pages.tools.he_total_remaining') }}
                        <span dir="ltr">— {{ $money($totals['unpaid']) }}</span>
                    </h3>
                    @if ($unpaidItems->isEmpty())
                        <p class="agricart-he__paid-empty">{{ __('agricart.pages.tools.he_remaining_empty') }}</p>
                    @else
                        <ul class="agricart-he__paid-list agricart-he__remain-list">
                            @foreach ($unpaidItems as $unpaid)
                                <li wire:key="he-unpaid-chip-{{ $unpaid->id }}">
                                    {{ $unpaid->name }}:
                                    <span>{{ $money($unpaid->expected_amount) }}</span>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            @endunless

            <div class="agricart-ro-list__table-wrap">
                @if ($items->isEmpty())
                    <div class="agricart-ro-list__empty">
                        {{ $viewingFolder ? __('agricart.pages.tools.he_folder_empty') : __('agricart.pages.tools.he_list_empty') }}
                    </div>
                @else
                    <table class="agricart-ro-list__table">
                        <thead>
                            <tr>
                                <th>{{ __('agricart.pages.tools.he_col_name') }}</th>
                                <th>{{ __('agricart.pages.tools.he_col_date') }}</th>
                                <th>{{ __('agricart.pages.tools.he_col_expected') }}</th>
                                @if ($viewingFolder)
                                    <th>{{ __('agricart.pages.tools.he_col_paid') }}</th>
                                    <th>{{ __('agricart.pages.tools.he_col_paid_at') }}</th>
                                @else
                                    <th>{{ __('agricart.pages.tools.he_col_status') }}</th>
                                @endif
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($items as $item)
                                <tr wire:key="he-item-{{ $item->id }}">
                                    <td class="agricart-ro-list__order">{{ $item->name }}</td>
                                    <td dir="ltr">{{ $item->expense_date?->format('d-m-Y') ?: '—' }}</td>
                                    <td dir="ltr">{{ $money($item->expected_amount) }}</td>
                                    @if ($viewingFolder)
                                        <td dir="ltr">{{ $money($item->paid_amount ?? $item->expected_amount) }}</td>
                                        <td dir="ltr">{{ optional($item->paid_at)->timezone(config('app.display_timezone', config('app.timezone')))->format('d-m-Y H:i') ?: '—' }}</td>
                                        <td>
                                            @if ($this->activeSheet()->status === 'open')
                                                <div class="agricart-ro-list__actions">
                                                    <x-filament::button size="sm" color="gray" outlined wire:click="unmarkPaid({{ $item->id }})">
                                                        {{ __('agricart.pages.tools.he_undo_pay') }}
                                                    </x-filament::button>
                                                </div>
                                            @endif
                                        </td>
                                    @else
                                        <td>
                                            <span class="agricart-he__badge agricart-he__badge--unpaid">{{ __('agricart.pages.tools.he_status_unpaid') }}</span>
                                        </td>
                                        <td>
                                            <div class="agricart-ro-list__actions">
                                                <x-filament::button size="sm" color="primary" wire:click="openPayForm({{ $item->id }})">
                                                    {{ __('agricart.pages.tools.he_pay') }}
                                                </x-filament::button>
                                                <x-filament::button size="sm" color="gray" outlined wire:click="addAgain({{ $item->id }})">
                                                    {{ __('agricart.pages.tools.he_add_again') }}
                                                </x-filament::button>
                                                <x-filament::button size="sm" color="danger" outlined wire:click="deleteExpense({{ $item->id }})" wire:confirm="{{ __('agricart.pages.tools.he_delete_confirm') }}">
                                                    {{ __('agricart.pages.tools.he_delete') }}
                                                </x-filament::button>
                                            </div>
                                        </td>
                                    @endif
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>
        </div>

        @unless ($viewingFolder)
            <div class="agricart-he__folders">
                <h3 class="agricart-he__folders-title">{{ __('agricart.pages.tools.he_folders_title') }}</h3>
                <p class="agricart-he__hint">{{ __('agricart.pages.tools.he_folders_hint') }}</p>
                <div class="agricart-ro-list__table-wrap">
                    @if ($folders->isEmpty())
                        <div class="agricart-ro-list__empty">{{ __('agricart.pages.tools.he_folders_empty') }}</div>
                    @else
                        <table class="agricart-ro-list__table">
                            <thead>
                                <tr>
                                    <th>{{ __('agricart.pages.tools.he_folder_col_month') }}</th>
                                    <th>{{ __('agricart.pages.tools.he_folder_col_status') }}</th>
                                    <th>{{ __('agricart.pages.tools.he_folder_col_paid') }}</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($folders as $folder)
                                    <tr wire:key="he-folder-{{ $folder->id }}">
                                        <td class="agricart-ro-list__order">
                                            <span class="agricart-he__folder-ico">
                                                <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M10 4H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V8a2 2 0 0 0-2-2h-8l-2-2z"/></svg>
                                                {{ $folder->title }}
                                            </span>
                                        </td>
                                        <td>
                                            @if ($folder->status === 'open')
                                                <span class="agricart-he__badge agricart-he__badge--open">{{ __('agricart.pages.tools.he_folder_status_open') }}</span>
                                            @else
                                                <span class="agricart-he__badge agricart-he__badge--closed">{{ __('agricart.pages.tools.he_folder_status_closed') }}</span>
                                            @endif
                                        </td>
                                        <td dir="ltr">{{ (int) ($folder->paid_count ?? 0) }}</td>
                                        <td>
                                            <div class="agricart-ro-list__actions">
                                                <x-filament::button size="sm" color="gray" outlined wire:click="openFolder({{ $folder->id }})">
                                                    {{ __('agricart.pages.tools.he_folder_open') }}
                                                </x-filament::button>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif
                </div>
            </div>
        @endunless
    </div>

    @if ($showAddForm && ! $viewingFolder)
        <div
            class="agricart-he__modal"
            wire:key="he-add-modal"
            x-data
            x-on:keydown.escape.window.prevent="$wire.closeAddForm()"
            role="dialog"
            aria-modal="true"
            aria-labelledby="he-add-modal-title"
        >
            <div class="agricart-he__modal-backdrop" x-on:click.prevent="$wire.closeAddForm()"></div>
            <div class="agricart-he__modal-card" x-on:click.stop>
                <div class="agricart-he__modal-head">
                    <h3 id="he-add-modal-title" class="agricart-he__modal-title">{{ __('agricart.pages.tools.he_add_expense') }}</h3>
                    <button type="button" class="agricart-he__modal-close" x-on:click.prevent="$wire.closeAddForm()" title="{{ __('agricart.pages.tools.he_close') }}">×</button>
                </div>
                <div class="agricart-he__modal-body">
                    <label class="agricart-he__field">
                        <span>{{ __('agricart.pages.tools.he_field_name') }}</span>
                        <input type="text" wire:model="expenseName" maxlength="160" autofocus placeholder="{{ __('agricart.pages.tools.he_field_name_ph') }}" />
                    </label>
                    <label class="agricart-he__field">
                        <span>{{ __('agricart.pages.tools.he_field_expected') }}</span>
                        <input type="number" min="1" step="1" inputmode="decimal" wire:model="expectedAmount" dir="ltr" placeholder="e.g. 5000" />
                    </label>
                    <label class="agricart-he__field">
                        <span>{{ __('agricart.pages.tools.he_field_date') }}</span>
                        <input type="date" wire:model="expenseDate" dir="ltr" />
                    </label>
                </div>
                <div class="agricart-he__modal-foot">
                    <button type="button" class="agricart-he__btn" x-on:click.prevent="$wire.closeAddForm()">{{ __('agricart.pages.tools.he_close') }}</button>
                    <button type="button" class="agricart-he__btn agricart-he__btn--primary" x-on:click.prevent="$wire.saveExpense()">{{ __('agricart.pages.tools.he_save') }}</button>
                </div>
            </div>
        </div>
    @endif

    @if ($payingItemId && ! $viewingFolder)
        <div
            class="agricart-he__modal"
            wire:key="he-pay-modal-{{ $payingItemId }}"
            x-data
            x-on:keydown.escape.window.prevent="$wire.closePayForm()"
            role="dialog"
            aria-modal="true"
            aria-labelledby="he-pay-modal-title"
        >
            <div class="agricart-he__modal-backdrop" x-on:click.prevent="$wire.closePayForm()"></div>
            <div class="agricart-he__modal-card" x-on:click.stop>
                <div class="agricart-he__modal-head">
                    <h3 id="he-pay-modal-title" class="agricart-he__modal-title">{{ __('agricart.pages.tools.he_pay_title') }}</h3>
                    <button type="button" class="agricart-he__modal-close" x-on:click.prevent="$wire.closePayForm()" title="{{ __('agricart.pages.tools.he_close') }}">×</button>
                </div>
                <div class="agricart-he__modal-body">
                    <p class="agricart-he__hint" style="margin:0">{{ $payingItemName }}</p>
                    <label class="agricart-he__field">
                        <span>{{ __('agricart.pages.tools.he_field_pay_amount') }}</span>
                        <input type="number" min="1" step="1" inputmode="decimal" wire:model="payAmount" dir="ltr" autofocus />
                    </label>
                </div>
                <div class="agricart-he__modal-foot">
                    <button type="button" class="agricart-he__btn" x-on:click.prevent="$wire.closePayForm()">{{ __('agricart.pages.tools.he_close') }}</button>
                    <button type="button" class="agricart-he__btn agricart-he__btn--primary" x-on:click.prevent="$wire.confirmPay()">{{ __('agricart.pages.tools.he_confirm_pay') }}</button>
                </div>
            </div>
        </div>
    @endif
</x-filament-panels::page>
