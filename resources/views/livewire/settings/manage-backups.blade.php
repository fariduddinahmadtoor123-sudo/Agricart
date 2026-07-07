<div class="agricart-backups" @if($busy) wire:poll.2s @endif>
    @if ($flashMessage)
        <div @class([
            'agricart-backups__alert',
            'is-success' => $flashType === 'success',
            'is-danger' => $flashType === 'danger',
            'is-warning' => $flashType === 'warning',
        ])>
            {{ $flashMessage }}
        </div>
    @endif

    @if ($busy)
        <div class="agricart-backups__progress">
            <span class="agricart-backups__spinner" aria-hidden="true"></span>
            <p>{{ __('agricart.backups.status.working') }}</p>
        </div>
    @endif

    <section class="agricart-backups__panel">
        <header class="agricart-backups__panel-head">
            <h2>{{ __('agricart.backups.sections.create') }}</h2>
            <p>{{ __('agricart.backups.sections.create_hint') }}</p>
        </header>

        <div class="agricart-backups__actions">
            <button type="button" class="agricart-backups__btn is-primary" wire:click="createFull" @disabled($busy || ! $canManage)>
                {{ __('agricart.backups.actions.create_full') }}
            </button>
            <button type="button" class="agricart-backups__btn" wire:click="createDatabase" @disabled($busy || ! $canManage)>
                {{ __('agricart.backups.actions.create_database') }}
            </button>
        </div>

        <ul class="agricart-backups__limits">
            <li>{{ __('agricart.backups.limits.retention', ['count' => $retentionCount]) }}</li>
            <li>{{ __('agricart.backups.limits.max_file', ['size' => $maxFileSize]) }}</li>
            <li>{{ __('agricart.backups.limits.total_used', ['size' => $totalSize]) }}</li>
        </ul>
    </section>

    <section class="agricart-backups__panel">
        <header class="agricart-backups__panel-head">
            <h2>{{ __('agricart.backups.sections.upload') }}</h2>
            <p>{{ __('agricart.backups.sections.upload_hint', ['size' => $maxFileSize]) }}</p>
        </header>

        <div class="agricart-backups__upload">
            <label class="agricart-backups__upload-field">
                <span class="agricart-form-panel__label">{{ __('agricart.backups.upload.field_label') }}</span>
                <input
                    type="file"
                    wire:model="uploadFile"
                    accept=".zip,application/zip"
                    @disabled($busy || ! $canManage)
                />
            </label>

            <div class="agricart-backups__actions">
                <button type="button" class="agricart-backups__btn is-primary" wire:click="uploadBackup" @disabled($busy || ! $canManage || ! $uploadFile)>
                    {{ __('agricart.backups.actions.upload') }}
                </button>
            </div>

            <div wire:loading wire:target="uploadFile" class="agricart-backups__upload-status">
                {{ __('agricart.backups.upload.reading') }}
            </div>

            @error('uploadFile')
                <p class="agricart-backups__upload-error">{{ $message }}</p>
            @enderror
        </div>
    </section>

    <section class="agricart-backups__panel">
        <header class="agricart-backups__panel-head">
            <h2>{{ __('agricart.backups.sections.export') }}</h2>
            <p>{{ __('agricart.backups.sections.export_hint') }}</p>
        </header>

        <div class="agricart-backups__actions">
            <a href="{{ $productsExportUrl }}" class="agricart-backups__btn" @class(['is-disabled' => ! $canManage]) @if(! $canManage) aria-disabled="true" @endif>
                {{ __('agricart.backups.actions.export_products') }}
            </a>
            <a href="{{ $categoriesExportUrl }}" class="agricart-backups__btn" @class(['is-disabled' => ! $canManage]) @if(! $canManage) aria-disabled="true" @endif>
                {{ __('agricart.backups.actions.export_categories') }}
            </a>
        </div>
    </section>

    <section class="agricart-backups__panel">
        <header class="agricart-backups__panel-head">
            <h2>{{ __('agricart.backups.sections.list') }}</h2>
            <p>{{ __('agricart.backups.sections.list_hint') }}</p>
        </header>

        @if ($backups === [])
            <div class="agricart-categories__empty">
                <p>{{ __('agricart.backups.list.empty') }}</p>
            </div>
        @else
            <div class="agricart-categories__table-wrap">
                <table class="agricart-categories__table agricart-backups__table">
                    <thead>
                        <tr>
                            <th>{{ __('agricart.backups.list.col_created') }}</th>
                            <th>{{ __('agricart.backups.list.col_type') }}</th>
                            <th>{{ __('agricart.backups.list.col_size') }}</th>
                            <th>{{ __('agricart.backups.list.col_files') }}</th>
                            <th>{{ __('agricart.backups.list.col_actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($backups as $backup)
                            <tr wire:key="backup-{{ $backup->id }}">
                                <td>{{ \Illuminate\Support\Carbon::parse($backup->createdAt)->format('Y-m-d H:i') }}</td>
                                <td>{{ __('agricart.backups.types.' . $backup->type) }}</td>
                                <td>{{ $backup->formattedSize() }}</td>
                                <td>{{ $backup->type === 'full' ? number_format($backup->filesCount) : '—' }}</td>
                                <td>
                                    <div class="agricart-categories__actions">
                                        <a href="{{ route('admin.backups.download', ['backupId' => $backup->id]) }}" class="agricart-categories__action">
                                            {{ __('agricart.backups.actions.download') }}
                                        </a>
                                        @if ($canManage)
                                            <button type="button" class="agricart-categories__action" wire:click="openRestore(@js($backup->id))" @disabled($busy)>
                                                {{ __('agricart.backups.actions.restore') }}
                                            </button>
                                            <button
                                                type="button"
                                                class="agricart-categories__action is-danger"
                                                wire:click="delete(@js($backup->id))"
                                                wire:confirm="{{ __('agricart.backups.messages.delete_confirm') }}"
                                                @disabled($busy)
                                            >
                                                {{ __('agricart.backups.actions.delete') }}
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>

    @if ($restoreBackupId)
        <div class="agricart-staff-approvals__modal" wire:keydown.escape.window="cancelRestore">
            <div class="agricart-staff-approvals__modal-backdrop" wire:click="cancelRestore"></div>
            <div class="agricart-staff-approvals__modal-card agricart-staff-approvals__modal-card--wide agricart-backups__restore-card" role="dialog" aria-modal="true">
                <h3 class="agricart-staff-approvals__modal-title">{{ __('agricart.backups.restore.title') }}</h3>
                <p class="agricart-backups__restore-warning">{{ __('agricart.backups.restore.warning') }}</p>

                <label class="agricart-backups__checkbox">
                    <input type="checkbox" wire:model="restoreSafetyBackup" />
                    <span>{{ __('agricart.backups.restore.safety_backup') }}</span>
                </label>

                <label class="agricart-form-panel__label" for="backup-restore-confirm">{{ __('agricart.backups.restore.confirm_label') }}</label>
                <input id="backup-restore-confirm" type="text" wire:model="restoreConfirm" class="agricart-backups__input" placeholder="RESTORE" />

                <div class="agricart-staff-approvals__modal-actions">
                    <button type="button" class="agricart-categories__action" wire:click="cancelRestore">{{ __('agricart.backups.actions.cancel') }}</button>
                    <button type="button" class="agricart-categories__action is-danger" wire:click="confirmRestore" @disabled($busy)>
                        {{ __('agricart.backups.actions.confirm_restore') }}
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
