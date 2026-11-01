<x-filament-panels::page>
    @php
        $usage = $this->usage();
        $files = $this->files();
        $reminders = $this->renewalReminders();
        $secrets = $this->secrets();
        $svc = app(\App\Services\Vault\PersonalVaultService::class);
    @endphp

    <div class="agricart-vault" style="max-width:920px;margin:0 auto;padding:0 0 2rem">
        <header style="margin-bottom:1.25rem">
            <h2 style="margin:0;font-size:1.35rem;font-weight:700;color:#0f172a">{{ __('agricart.pages.vault.my_box_title') }}</h2>
            <p style="margin:.35rem 0 0;color:#64748b;font-size:.9rem">{{ __('agricart.pages.vault.my_box_hint') }}</p>
        </header>

        <div style="background:linear-gradient(135deg,#f8fafc,#eef2ff);border:1px solid rgba(15,23,42,.08);border-radius:12px;padding:1rem 1.15rem;margin-bottom:1.25rem">
            <div style="display:flex;justify-content:space-between;gap:1rem;flex-wrap:wrap;font-size:.85rem;color:#334155">
                <span>{{ __('agricart.pages.vault.usage', ['used' => $usage['used_display'], 'quota' => $usage['quota_display']]) }}</span>
                <span>{{ __('agricart.pages.vault.files_count', ['count' => $usage['files'], 'max' => $usage['max_files']]) }}</span>
            </div>
            <div style="margin-top:.65rem;height:8px;background:#e2e8f0;border-radius:999px;overflow:hidden">
                <div style="height:100%;width:{{ min(100, $usage['percent']) }}%;background:#0f766e;border-radius:999px"></div>
            </div>
        </div>

        <form wire:submit="saveUpload" style="display:grid;gap:.75rem;background:#fff;border:1px solid rgba(15,23,42,.08);border-radius:12px;padding:1rem;margin-bottom:1.25rem">
            <div>
                <label style="display:block;font-size:.8rem;font-weight:600;color:#475569;margin-bottom:.35rem">{{ __('agricart.pages.vault.doc_type') }}</label>
                <select wire:model="docType" style="width:100%;border:1px solid #cbd5e1;border-radius:8px;padding:.55rem .7rem;background:#fff">
                    @foreach (\App\Models\PersonalVaultFile::docTypeOptions() as $value => $text)
                        <option value="{{ $value }}">{{ $text }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label style="display:block;font-size:.8rem;font-weight:600;color:#475569;margin-bottom:.35rem">{{ __('agricart.pages.vault.label') }}</label>
                <input type="text" wire:model="label" style="width:100%;border:1px solid #cbd5e1;border-radius:8px;padding:.55rem .7rem" placeholder="{{ __('agricart.pages.vault.label_ph') }}" />
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:.65rem">
                <div>
                    <label style="display:block;font-size:.8rem;font-weight:600;color:#475569;margin-bottom:.35rem">{{ __('agricart.pages.vault.valid_from') }}</label>
                    <input type="date" wire:model="validFrom" style="width:100%;border:1px solid #cbd5e1;border-radius:8px;padding:.55rem .7rem" />
                </div>
                <div>
                    <label style="display:block;font-size:.8rem;font-weight:600;color:#475569;margin-bottom:.35rem">{{ __('agricart.pages.vault.valid_until') }}</label>
                    <input type="date" wire:model="validUntil" style="width:100%;border:1px solid #cbd5e1;border-radius:8px;padding:.55rem .7rem" />
                </div>
            </div>
            <p style="margin:0;font-size:.75rem;color:#94a3b8">{{ __('agricart.pages.vault.valid_hint') }}</p>
            <div>
                <label style="display:block;font-size:.8rem;font-weight:600;color:#475569;margin-bottom:.35rem">{{ __('agricart.pages.vault.file') }}</label>
                <input type="file" wire:model="upload" style="width:100%;font-size:.85rem" />
                <p style="margin:.35rem 0 0;font-size:.75rem;color:#94a3b8">{{ __('agricart.pages.vault.file_hint', ['max' => $svc->formatBytes($svc->maxFileBytes())]) }}</p>
                <div wire:loading wire:target="upload" style="font-size:.8rem;color:#0f766e;margin-top:.35rem">{{ __('agricart.pages.vault.uploading') }}</div>
            </div>
            <div>
                <x-filament::button type="submit" color="primary" wire:loading.attr="disabled">{{ __('agricart.pages.vault.upload') }}</x-filament::button>
            </div>
        </form>

        @include('filament.pages.vault.partials.renewal-filters', [
            'reminders' => $reminders,
            'activeFilter' => $this->fileFilter,
        ])

        <div style="background:#fff;border:1px solid rgba(15,23,42,.08);border-radius:12px;overflow:hidden">
            @if ($files->isEmpty())
                <p style="padding:1.5rem;text-align:center;color:#64748b;margin:0">
                    {{ $this->fileFilter === 'all' ? __('agricart.pages.vault.empty') : __('agricart.pages.vault.filter_empty') }}
                </p>
            @else
                <table style="width:100%;border-collapse:collapse;font-size:.875rem">
                    <thead>
                        <tr style="background:#f8fafc;text-align:start">
                            <th style="padding:.7rem .9rem;font-weight:600;color:#475569">{{ __('agricart.pages.vault.col_name') }}</th>
                            <th style="padding:.7rem .9rem;font-weight:600;color:#475569">{{ __('agricart.pages.vault.col_type') }}</th>
                            <th style="padding:.7rem .9rem;font-weight:600;color:#475569">{{ __('agricart.pages.vault.col_validity') }}</th>
                            <th style="padding:.7rem .9rem;font-weight:600;color:#475569">{{ __('agricart.pages.vault.col_size') }}</th>
                            <th style="padding:.7rem .9rem;font-weight:600;color:#475569">{{ __('agricart.pages.vault.col_date') }}</th>
                            <th style="padding:.7rem .9rem"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($files as $file)
                            @php $vStatus = $file->validityStatus(); @endphp
                            <tr wire:key="vault-file-{{ $file->id }}" style="border-top:1px solid rgba(15,23,42,.06)">
                                <td style="padding:.7rem .9rem">
                                    <strong style="display:block;color:#0f172a">{{ $file->label ?: $file->original_name }}</strong>
                                    @if ($file->label)
                                        <span style="font-size:.75rem;color:#94a3b8">{{ $file->original_name }}</span>
                                    @endif
                                </td>
                                <td style="padding:.7rem .9rem;color:#0f766e;white-space:nowrap;font-weight:600">{{ $file->docTypeLabel() }}</td>
                                <td style="padding:.7rem .9rem;white-space:nowrap;direction:ltr">
                                    <span style="color:#334155">{{ $file->validityLabel() }}</span>
                                    @if ($vStatus === 'expired')
                                        <span style="display:block;font-size:.72rem;color:#b91c1c;font-weight:700">{{ __('agricart.pages.vault.status_expired') }}</span>
                                    @elseif ($vStatus === 'soon')
                                        <span style="display:block;font-size:.72rem;color:#b45309;font-weight:700">{{ __('agricart.pages.vault.status_soon') }}</span>
                                    @endif
                                </td>
                                <td style="padding:.7rem .9rem;color:#64748b;white-space:nowrap">{{ $svc->formatBytes((int) $file->size_bytes) }}</td>
                                <td style="padding:.7rem .9rem;color:#64748b;white-space:nowrap;direction:ltr">{{ optional($file->created_at)->timezone(config('app.display_timezone', config('app.timezone')))->format('d-m-Y H:i') }}</td>
                                <td style="padding:.7rem .9rem;text-align:end;white-space:nowrap">
                                    <a href="{{ route('admin.vault.files.show', $file) }}" target="_blank" rel="noopener" style="margin-inline-end:.5rem;color:#0f766e;font-weight:600;text-decoration:none">{{ __('agricart.pages.vault.open') }}</a>
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
            'editingId' => $this->editingSecretId,
        ])
    </div>
</x-filament-panels::page>
