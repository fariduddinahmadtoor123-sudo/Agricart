<x-filament-panels::page>
    @php
        $users = $this->staffUsers();
        $owner = $this->selectedOwner();
        $usage = $this->usage();
        $files = $this->files();
        $reminders = $this->renewalReminders();
        $secrets = $this->secrets();
        $svc = app(\App\Services\Vault\PersonalVaultService::class);
    @endphp

    <div class="agricart-vault" style="display:grid;grid-template-columns:minmax(220px,280px) 1fr;gap:1rem;align-items:start">
        <aside style="background:#fff;border:1px solid rgba(15,23,42,.08);border-radius:12px;padding:.85rem;max-height:70vh;overflow:auto">
            <h2 style="margin:0 0 .65rem;font-size:1rem;font-weight:700">{{ __('agricart.pages.vault.all_boxes_title') }}</h2>
            <input type="search" wire:model.live.debounce.300ms="search" placeholder="{{ __('agricart.pages.vault.search_user') }}" style="width:100%;border:1px solid #cbd5e1;border-radius:8px;padding:.45rem .6rem;margin-bottom:.65rem;font-size:.85rem" />
            <ul style="list-style:none;margin:0;padding:0;display:grid;gap:.25rem">
                @forelse ($users as $user)
                    <li>
                        <button
                            type="button"
                            wire:click="selectUser({{ $user->id }})"
                            style="width:100%;text-align:start;border:1px solid {{ (int) $this->selectedUserId === (int) $user->id ? '#0f766e' : 'transparent' }};background:{{ (int) $this->selectedUserId === (int) $user->id ? '#ecfdf5' : '#f8fafc' }};border-radius:8px;padding:.55rem .65rem;cursor:pointer"
                        >
                            <strong style="display:block;font-size:.85rem;color:#0f172a">{{ $user->name }}</strong>
                            <span style="font-size:.72rem;color:#94a3b8">{{ $user->email }}</span>
                        </button>
                    </li>
                @empty
                    <li style="padding:.75rem;color:#94a3b8;font-size:.85rem">{{ __('agricart.pages.vault.no_users') }}</li>
                @endforelse
            </ul>
        </aside>

        <section>
            @if (! $owner)
                <p style="padding:2rem;text-align:center;color:#64748b;background:#fff;border:1px solid rgba(15,23,42,.08);border-radius:12px">{{ __('agricart.pages.vault.pick_user') }}</p>
            @else
                <header style="margin-bottom:1rem">
                    <h2 style="margin:0;font-size:1.2rem;font-weight:700">{{ $owner->name }}</h2>
                    <p style="margin:.25rem 0 0;color:#64748b;font-size:.85rem">{{ $owner->email }}</p>
                </header>

                @if ($usage)
                    <div style="background:linear-gradient(135deg,#f8fafc,#ecfdf5);border:1px solid rgba(15,23,42,.08);border-radius:12px;padding:1rem;margin-bottom:1rem">
                        <div style="display:flex;justify-content:space-between;gap:1rem;flex-wrap:wrap;font-size:.85rem">
                            <span>{{ __('agricart.pages.vault.usage', ['used' => $usage['used_display'], 'quota' => $usage['quota_display']]) }}</span>
                            <span>{{ __('agricart.pages.vault.files_count', ['count' => $usage['files'], 'max' => $usage['max_files']]) }}</span>
                        </div>
                        <div style="margin-top:.65rem;height:8px;background:#e2e8f0;border-radius:999px;overflow:hidden">
                            <div style="height:100%;width:{{ min(100, $usage['percent']) }}%;background:#0f766e;border-radius:999px"></div>
                        </div>
                        <form wire:submit="saveQuota" style="display:flex;gap:.5rem;align-items:end;margin-top:.85rem;flex-wrap:wrap">
                            <div>
                                <label style="display:block;font-size:.75rem;font-weight:600;color:#475569;margin-bottom:.25rem">{{ __('agricart.pages.vault.quota_mb') }}</label>
                                <input type="number" min="1" max="5120" wire:model="quotaMb" style="width:7rem;border:1px solid #cbd5e1;border-radius:8px;padding:.45rem .55rem" />
                            </div>
                            <x-filament::button type="submit" size="sm" color="gray">{{ __('agricart.pages.vault.save_quota') }}</x-filament::button>
                        </form>
                    </div>
                @endif

                <form wire:submit="saveUpload" style="display:grid;gap:.65rem;background:#fff;border:1px solid rgba(15,23,42,.08);border-radius:12px;padding:1rem;margin-bottom:1rem">
                    <select wire:model="docType" style="border:1px solid #cbd5e1;border-radius:8px;padding:.5rem .65rem;background:#fff">
                        @foreach (\App\Models\PersonalVaultFile::docTypeOptions() as $value => $text)
                            <option value="{{ $value }}">{{ $text }}</option>
                        @endforeach
                    </select>
                    <input type="text" wire:model="label" placeholder="{{ __('agricart.pages.vault.label_ph') }}" style="border:1px solid #cbd5e1;border-radius:8px;padding:.5rem .65rem" />
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:.5rem">
                        <input type="date" wire:model="validFrom" title="{{ __('agricart.pages.vault.valid_from') }}" style="border:1px solid #cbd5e1;border-radius:8px;padding:.5rem .65rem" />
                        <input type="date" wire:model="validUntil" title="{{ __('agricart.pages.vault.valid_until') }}" style="border:1px solid #cbd5e1;border-radius:8px;padding:.5rem .65rem" />
                    </div>
                    <p style="margin:0;font-size:.75rem;color:#94a3b8">{{ __('agricart.pages.vault.valid_hint') }}</p>
                    <input type="file" wire:model="upload" />
                    <div>
                        <x-filament::button type="submit" color="primary">{{ __('agricart.pages.vault.upload') }}</x-filament::button>
                    </div>
                </form>

                @if ($reminders)
                    @include('filament.pages.vault.partials.renewal-filters', [
                        'reminders' => $reminders,
                        'activeFilter' => $this->fileFilter,
                    ])
                @endif

                <div style="background:#fff;border:1px solid rgba(15,23,42,.08);border-radius:12px;overflow:hidden">
                    @if ($files->isEmpty())
                        <p style="padding:1.25rem;text-align:center;color:#64748b;margin:0">
                            {{ $this->fileFilter === 'all' ? __('agricart.pages.vault.empty') : __('agricart.pages.vault.filter_empty') }}
                        </p>
                    @else
                        <table style="width:100%;border-collapse:collapse;font-size:.875rem">
                            <thead>
                                <tr style="background:#f8fafc;text-align:start">
                                    <th style="padding:.65rem .85rem">{{ __('agricart.pages.vault.col_name') }}</th>
                                    <th style="padding:.65rem .85rem">{{ __('agricart.pages.vault.col_type') }}</th>
                                    <th style="padding:.65rem .85rem">{{ __('agricart.pages.vault.col_validity') }}</th>
                                    <th style="padding:.65rem .85rem">{{ __('agricart.pages.vault.col_size') }}</th>
                                    <th style="padding:.65rem .85rem">{{ __('agricart.pages.vault.col_date') }}</th>
                                    <th style="padding:.65rem .85rem"></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($files as $file)
                                    @php $vStatus = $file->validityStatus(); @endphp
                                    <tr wire:key="sa-vault-{{ $file->id }}" style="border-top:1px solid rgba(15,23,42,.06)">
                                        <td style="padding:.65rem .85rem">
                                            <strong>{{ $file->label ?: $file->original_name }}</strong>
                                        </td>
                                        <td style="padding:.65rem .85rem;color:#0f766e;font-weight:600;white-space:nowrap">{{ $file->docTypeLabel() }}</td>
                                        <td style="padding:.65rem .85rem;white-space:nowrap;direction:ltr">
                                            <span style="color:#334155">{{ $file->validityLabel() }}</span>
                                            @if ($vStatus === 'expired')
                                                <span style="display:block;font-size:.72rem;color:#b91c1c;font-weight:700">{{ __('agricart.pages.vault.status_expired') }}</span>
                                            @elseif ($vStatus === 'soon')
                                                <span style="display:block;font-size:.72rem;color:#b45309;font-weight:700">{{ __('agricart.pages.vault.status_soon') }}</span>
                                            @endif
                                        </td>
                                        <td style="padding:.65rem .85rem;color:#64748b">{{ $svc->formatBytes((int) $file->size_bytes) }}</td>
                                        <td style="padding:.65rem .85rem;color:#64748b;direction:ltr">{{ optional($file->created_at)->timezone(config('app.display_timezone', config('app.timezone')))->format('d-m-Y H:i') }}</td>
                                        <td style="padding:.65rem .85rem;text-align:end">
                                            <a href="{{ route('admin.vault.files.show', $file) }}" target="_blank" rel="noopener" style="margin-inline-end:.45rem;color:#0f766e;font-weight:600;text-decoration:none">{{ __('agricart.pages.vault.open') }}</a>
                                            <button type="button" wire:click="deleteFile({{ $file->id }})" wire:confirm="{{ __('agricart.pages.vault.delete_confirm') }}" style="border:0;background:transparent;color:#b91c1c;font-weight:600;cursor:pointer">{{ __('agricart.pages.vault.delete') }}</button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif
                </div>

                @include('filament.pages.vault.partials.secrets-panel', [
                    'secrets' => $secrets,
                    'revealed' => $this->revealedSecretIds,
                    'editingId' => null,
                ])
            @endif
        </section>
    </div>
</x-filament-panels::page>
