@php
    /** @var \Illuminate\Support\Collection<int, \App\Models\PersonalVaultSecret> $secrets */
    /** @var array<int, true> $revealed */
    $revealed = $revealed ?? [];
    $editingId = $editingId ?? null;
    $svc = app(\App\Services\Vault\PersonalVaultService::class);
@endphp

<section style="margin-top:1.75rem">
    <header style="margin-bottom:.85rem">
        <h3 style="margin:0;font-size:1.05rem;font-weight:700;color:#0f172a">{{ __('agricart.pages.vault.secrets_title') }}</h3>
        <p style="margin:.3rem 0 0;color:#64748b;font-size:.82rem">{{ __('agricart.pages.vault.secrets_hint', ['max' => $svc->maxSecretsPerUser()]) }}</p>
    </header>

    <form wire:submit="saveSecret" style="display:grid;gap:.65rem;background:#fff;border:1px solid rgba(15,23,42,.08);border-radius:12px;padding:1rem;margin-bottom:1rem">
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(11rem,1fr));gap:.65rem">
            <label style="display:flex;flex-direction:column;gap:.3rem;font-size:.78rem;font-weight:600;color:#475569">
                {{ __('agricart.pages.vault.secret_title') }}
                <input type="text" wire:model="secretTitle" required style="border:1px solid #cbd5e1;border-radius:8px;padding:.5rem .65rem;font-weight:500;color:#0f172a" placeholder="{{ __('agricart.pages.vault.secret_title_ph') }}" />
            </label>
            <label style="display:flex;flex-direction:column;gap:.3rem;font-size:.78rem;font-weight:600;color:#475569">
                {{ __('agricart.pages.vault.secret_site') }}
                <input type="text" wire:model="secretSiteUrl" style="border:1px solid #cbd5e1;border-radius:8px;padding:.5rem .65rem;font-weight:500;color:#0f172a;direction:ltr" placeholder="https://…" />
            </label>
            <label style="display:flex;flex-direction:column;gap:.3rem;font-size:.78rem;font-weight:600;color:#475569">
                {{ __('agricart.pages.vault.secret_username') }}
                <input type="text" wire:model="secretUsername" autocomplete="off" style="border:1px solid #cbd5e1;border-radius:8px;padding:.5rem .65rem;font-weight:500;color:#0f172a;direction:ltr" />
            </label>
            <label style="display:flex;flex-direction:column;gap:.3rem;font-size:.78rem;font-weight:600;color:#475569">
                {{ __('agricart.pages.vault.secret_password') }}
                <input type="text" wire:model="secretPassword" autocomplete="new-password" style="border:1px solid #cbd5e1;border-radius:8px;padding:.5rem .65rem;font-weight:500;color:#0f172a;direction:ltr" placeholder="{{ $editingId ? __('agricart.pages.vault.secret_password_keep') : '' }}" />
            </label>
        </div>
        <label style="display:flex;flex-direction:column;gap:.3rem;font-size:.78rem;font-weight:600;color:#475569">
            {{ __('agricart.pages.vault.secret_notes') }}
            <textarea wire:model="secretNotes" rows="2" style="border:1px solid #cbd5e1;border-radius:8px;padding:.5rem .65rem;font-weight:500;color:#0f172a;resize:vertical"></textarea>
        </label>
        <div style="display:flex;gap:.5rem;flex-wrap:wrap">
            <x-filament::button type="submit" color="primary">
                {{ $editingId ? __('agricart.pages.vault.secret_update') : __('agricart.pages.vault.secret_save') }}
            </x-filament::button>
            @if ($editingId)
                <x-filament::button type="button" color="gray" wire:click="cancelEditSecret">{{ __('agricart.pages.vault.secret_cancel') }}</x-filament::button>
            @endif
        </div>
    </form>

    <div style="background:#fff;border:1px solid rgba(15,23,42,.08);border-radius:12px;overflow:hidden">
        @if ($secrets->isEmpty())
            <p style="padding:1.25rem;text-align:center;color:#64748b;margin:0">{{ __('agricart.pages.vault.secrets_empty') }}</p>
        @else
            <div style="overflow-x:auto">
                <table style="width:100%;border-collapse:collapse;font-size:.875rem;min-width:36rem">
                    <thead>
                        <tr style="background:#f8fafc;text-align:start">
                            <th style="padding:.65rem .85rem;font-weight:600;color:#475569">{{ __('agricart.pages.vault.secret_title') }}</th>
                            <th style="padding:.65rem .85rem;font-weight:600;color:#475569">{{ __('agricart.pages.vault.secret_username') }}</th>
                            <th style="padding:.65rem .85rem;font-weight:600;color:#475569">{{ __('agricart.pages.vault.secret_password') }}</th>
                            <th style="padding:.65rem .85rem"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($secrets as $secret)
                            @php $isOpen = isset($revealed[$secret->id]); @endphp
                            <tr wire:key="vault-secret-{{ $secret->id }}" style="border-top:1px solid rgba(15,23,42,.06)">
                                <td style="padding:.65rem .85rem;vertical-align:top">
                                    <strong style="display:block;color:#0f172a">{{ $secret->title }}</strong>
                                    @if ($secret->site_url)
                                        <a href="{{ str_starts_with($secret->site_url, 'http') ? $secret->site_url : 'https://'.$secret->site_url }}" target="_blank" rel="noopener" style="font-size:.75rem;color:#0f766e;word-break:break-all">{{ $secret->site_url }}</a>
                                    @endif
                                    @if ($secret->notes)
                                        <span style="display:block;margin-top:.25rem;font-size:.75rem;color:#94a3b8">{{ $secret->notes }}</span>
                                    @endif
                                </td>
                                <td style="padding:.65rem .85rem;direction:ltr;color:#334155;vertical-align:top">{{ $secret->username ?: '—' }}</td>
                                <td style="padding:.65rem .85rem;direction:ltr;font-family:ui-monospace,monospace;vertical-align:top;color:#0f172a">
                                    {{ $isOpen ? $secret->password : '••••••••' }}
                                </td>
                                <td style="padding:.65rem .85rem;text-align:end;white-space:nowrap;vertical-align:top">
                                    <button type="button" wire:click="toggleRevealSecret({{ $secret->id }})" style="border:0;background:transparent;color:#0f766e;font-weight:600;cursor:pointer;margin-inline-end:.35rem">
                                        {{ $isOpen ? __('agricart.pages.vault.secret_hide') : __('agricart.pages.vault.secret_show') }}
                                    </button>
                                    @if (method_exists($this, 'startEditSecret'))
                                        <button type="button" wire:click="startEditSecret({{ $secret->id }})" style="border:0;background:transparent;color:#475569;font-weight:600;cursor:pointer;margin-inline-end:.35rem">{{ __('agricart.pages.vault.secret_edit') }}</button>
                                    @endif
                                    <button type="button" wire:click="deleteSecret({{ $secret->id }})" wire:confirm="{{ __('agricart.pages.vault.secret_delete_confirm') }}" style="border:0;background:transparent;color:#b91c1c;font-weight:600;cursor:pointer">{{ __('agricart.pages.vault.delete') }}</button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</section>
