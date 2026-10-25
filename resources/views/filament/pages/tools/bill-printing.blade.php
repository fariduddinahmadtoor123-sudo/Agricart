<x-filament-panels::page>
    <style>
        .agricart-bp{display:flex;flex-direction:column;gap:1rem}
        .agricart-bp__hint{margin:0;font-size:.875rem;color:#64748b;line-height:1.5;max-width:48rem}
        .agricart-bp-toolbar-slot{flex:1 1 auto;min-width:0;display:flex;align-items:center}
        .agricart-bp-chrome{display:flex;align-items:center;gap:.5rem;width:100%;flex-wrap:wrap}
        .agricart-bp__count{font-size:.8125rem;font-weight:700;color:#0f172a}
        .agricart-bp__btn{
            appearance:none;border:1px solid #cbd5e1;background:#fff;color:#0f172a;
            font-size:.8125rem;font-weight:700;padding:.4rem .75rem;border-radius:.5rem;cursor:pointer
        }
        .agricart-bp__btn:hover{background:#f8fafc}
        .agricart-bp__btn--primary{background:#0f172a;border-color:#0f172a;color:#fff}
        .agricart-bp__btn--primary:hover{background:#1e293b}
        .agricart-bp__grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(9.5rem,1fr));gap:.75rem}
        .agricart-bp__card{
            position:relative;border:1px solid rgba(15,23,42,.1);border-radius:.75rem;background:#fff;
            overflow:hidden;aspect-ratio:3/4;display:flex;align-items:center;justify-content:center
        }
        .agricart-bp__card img{width:100%;height:100%;object-fit:contain;background:#f8fafc}
        .agricart-bp__card-meta{
            position:absolute;inset-inline:0;bottom:0;padding:.35rem .45rem;font-size:.65rem;font-weight:700;
            color:#fff;background:linear-gradient(transparent,rgba(15,23,42,.72))
        }
        .agricart-bp__card-meta span{overflow:hidden;text-overflow:ellipsis;white-space:nowrap;display:block}
        .agricart-bp__remove{
            appearance:none;border:0;background:rgba(185,28,28,.92);color:#fff;width:1.6rem;height:1.6rem;
            border-radius:999px;position:absolute;top:.4rem;inset-inline-end:.4rem;cursor:pointer;font-size:.85rem;line-height:1;font-weight:700
        }
        .agricart-bp__empty{
            border:1px dashed rgba(15,23,42,.18);border-radius:.85rem;padding:2.25rem 1rem;text-align:center;
            color:#64748b;font-size:.875rem;background:rgba(248,250,252,.8)
        }
        .agricart-bp__folder-title{margin:0;font-size:1rem;font-weight:800;color:#0f172a}
        .agricart-bp__print-root{display:none}
        .agricart-ro-list{display:flex;flex-direction:column;gap:.75rem}
        .agricart-ro-list__hint{font-size:.8125rem;color:#64748b}
        .agricart-ro-list__table-wrap{overflow:auto;border-radius:.75rem;background:#fff;border:1px solid rgba(15,23,42,.08)}
        .agricart-ro-list__table{width:100%;border-collapse:collapse;font-size:.8125rem}
        .agricart-ro-list__table th{text-align:start;padding:.65rem .85rem;font-size:.6875rem;font-weight:700;text-transform:uppercase;color:#64748b;border-bottom:1px solid rgba(15,23,42,.08);background:rgba(148,163,184,.08)}
        .agricart-ro-list__table td{padding:.7rem .85rem;border-bottom:1px solid rgba(15,23,42,.06);vertical-align:middle}
        .agricart-ro-list__order{font-weight:700;direction:ltr}
        .agricart-ro-list__actions{display:flex;gap:.35rem;justify-content:flex-end;flex-wrap:wrap}
        .agricart-ro-list__empty{padding:1.5rem;text-align:center;color:#64748b}
        .agricart-bp__folder-ico{display:inline-flex;align-items:center;gap:.45rem}
        .agricart-bp__folder-ico svg{width:1.15rem;height:1.15rem;color:#ca8a04;flex:0 0 auto}
        @media print{
            @page{size:A4 portrait;margin:0}
            html,body{background:#fff!important;margin:0!important;padding:0!important}
            .fi-sidebar,.fi-sidebar-close-overlay,.fi-topbar,.fi-topbar-ctn,.fi-header,.fi-breadcrumbs,.fi-page-header,
            .fi-page-sub-navigation-tabs,.fi-global-search,.fi-notifications,.fi-modal,.agricart-catalog-subnav,
            .agricart-catalog-toolbar,.agricart-topbar-actions,.agricart-bp__hint,.agricart-bp-toolbar-slot,
            .agricart-bp__grid,.agricart-bp__empty,.agricart-bp__folder-title,.agricart-ro-list,.no-print{display:none!important}
            .fi-main-ctn,.fi-main,.fi-page,.fi-page-main,.fi-page-content,.fi-wi-widget,.fi-section,
            .fi-section-content-ctn,.fi-section-content,.agricart-bp{
                margin:0!important;padding:0!important;border:0!important;box-shadow:none!important;
                background:transparent!important;max-width:none!important;width:100%!important;overflow:visible!important
            }
            .agricart-bp__print-root{display:block!important}
            .agricart-bp__print-page{
                width:210mm;height:297mm;margin:0;padding:0;box-sizing:border-box;
                page-break-after:always;break-after:page;
                display:flex;align-items:center;justify-content:center;overflow:hidden;background:#fff
            }
            .agricart-bp__print-page:last-child{page-break-after:auto;break-after:auto}
            .agricart-bp__print-page img{max-width:100%;max-height:100%;width:auto;height:auto;object-fit:contain;display:block}
        }
    </style>

    @if ($screen === 'form')
        @php $rows = $this->imageRows(); @endphp
        <div
            class="agricart-bp"
            x-data
            x-init="window.dispatchEvent(new CustomEvent('tools-bill-printing-screen',{detail:{screen:'form',formMode:@js($this->isViewMode() ? 'view' : 'edit')}}))"
            x-on:tools-bill-printing-print.window="window.print()"
            wire:key="bp-form-{{ $editingFolderId ?? 0 }}-{{ $formMode }}-{{ count($rows) }}"
        >
            @unless ($this->isViewMode())
                @teleport('#agricart-bp-toolbar-slot')
                    <div class="agricart-bp-chrome print:hidden">
                        <label class="agricart-bp__btn agricart-bp__btn--primary" style="cursor:pointer;margin:0">
                            {{ __('agricart.pages.tools.bp_add') }}
                            <input type="file" accept="image/*" multiple wire:model="uploadBatch" class="sr-only" />
                        </label>
                        <label class="agricart-bp__btn" style="cursor:pointer;margin:0">
                            {{ __('agricart.pages.tools.bp_camera') }}
                            <input type="file" accept="image/*" capture="environment" wire:model="uploadBatch" class="sr-only" />
                        </label>
                        <span class="agricart-bp__count" wire:loading wire:target="uploadBatch">{{ __('agricart.pages.tools.bp_uploading') }}</span>
                        @if (count($rows) > 0)
                            <span class="agricart-bp__count">{{ __('agricart.pages.tools.bp_count', ['count' => count($rows)]) }}</span>
                        @endif
                    </div>
                @endteleport
            @endunless

            <p class="agricart-bp__hint no-print">
                @if ($this->isViewMode())
                    {{ __('agricart.pages.tools.bp_view_hint') }}
                @else
                    {{ __('agricart.pages.tools.bp_form_hint') }}
                @endif
            </p>
            <h2 class="agricart-bp__folder-title no-print">{{ $folderName }}</h2>

            @if (count($rows) === 0)
                <div class="agricart-bp__empty no-print">{{ __('agricart.pages.tools.bp_empty') }}</div>
            @else
                <div class="agricart-bp__grid no-print">
                    @foreach ($rows as $index => $row)
                        <div class="agricart-bp__card" wire:key="bp-img-{{ $editingFolderId }}-{{ $index }}-{{ md5($row['path']) }}">
                            <img src="{{ $row['url'] }}" alt="{{ $row['name'] }}" />
                            @unless ($this->isViewMode())
                                <button type="button" class="agricart-bp__remove" wire:click="removeImage({{ $index }})" title="{{ __('agricart.pages.tools.bp_remove') }}">×</button>
                            @endunless
                            <div class="agricart-bp__card-meta">
                                <span>{{ ($index + 1) }}. {{ $row['name'] }}</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif

            <div class="agricart-bp__print-root" aria-hidden="true">
                @foreach ($rows as $row)
                    <div class="agricart-bp__print-page">
                        <img src="{{ $row['url'] }}" alt="{{ $row['name'] }}" />
                    </div>
                @endforeach
            </div>
        </div>
    @else
        @php $folders = $this->listedFolders(); @endphp
        <div class="agricart-ro-list" x-data x-init="window.dispatchEvent(new CustomEvent('tools-bill-printing-screen',{detail:{screen:'list'}}))">
            <p class="agricart-ro-list__hint">{{ __('agricart.pages.tools.bp_list_hint') }}</p>
            <div class="agricart-ro-list__table-wrap">
                @if ($folders->isEmpty())
                    <div class="agricart-ro-list__empty">{{ __('agricart.pages.tools.bp_list_empty') }}</div>
                @else
                    <table class="agricart-ro-list__table">
                        <thead>
                            <tr>
                                <th>{{ __('agricart.pages.tools.bp_list_col_folder') }}</th>
                                <th>{{ __('agricart.pages.tools.bp_list_col_date') }}</th>
                                <th>{{ __('agricart.pages.tools.bp_list_col_images') }}</th>
                                <th>{{ __('agricart.pages.tools.bp_list_col_updated') }}</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($folders as $folder)
                                <tr wire:key="bp-folder-{{ $folder->id }}">
                                    <td class="agricart-ro-list__order">
                                        <span class="agricart-bp__folder-ico">
                                            <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M10 4H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V8a2 2 0 0 0-2-2h-8l-2-2z"/></svg>
                                            {{ $folder->folder_name }}
                                        </span>
                                    </td>
                                    <td dir="ltr">{{ $folder->folder_date ?: '—' }}</td>
                                    <td dir="ltr">{{ (int) $folder->image_count }}</td>
                                    <td dir="ltr">{{ optional($folder->updated_at)->timezone(config('app.display_timezone', config('app.timezone')))->format('d-m-Y H:i') }}</td>
                                    <td>
                                        <div class="agricart-ro-list__actions">
                                            <x-filament::button size="sm" color="gray" outlined wire:click="openFolder({{ $folder->id }})">{{ __('agricart.pages.tools.bp_list_open') }}</x-filament::button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>
        </div>
    @endif
</x-filament-panels::page>
