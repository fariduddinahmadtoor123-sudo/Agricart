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
            <h2>{{ __('agricart.backups.sections.compatibility') }}</h2>
            <p>{{ __('agricart.backups.sections.compatibility_hint') }}</p>
        </header>
        <ul class="agricart-backups__limits">
            <li>{{ __('agricart.backups.compatibility.driver', ['driver' => $databaseDriver]) }}</li>
            @if ($databaseDriver === 'sqlite')
                <li class="agricart-backups__upload-hint is-warning">{{ __('agricart.backups.compatibility.sqlite_warning') }}</li>
            @else
                <li>{{ __('agricart.backups.compatibility.mysql_ok') }}</li>
            @endif
            <li>{{ __('agricart.backups.compatibility.flow') }}</li>
        </ul>
    </section>

    <section class="agricart-backups__panel">
        <header class="agricart-backups__panel-head">
            <h2>{{ __('agricart.backups.sections.upload') }}</h2>
            <p>{{ __('agricart.backups.sections.upload_hint', ['size' => $maxFileSize]) }}</p>
        </header>

        <div class="agricart-backups__upload" id="agricart-backup-upload" data-upload-url="{{ $uploadUrl }}">
            @if ($uploadLimitBelowBackupMax)
                <p class="agricart-backups__upload-hint is-warning">
                    {{ __('agricart.backups.upload.php_limit_hint', [
                        'php_limit' => $phpUploadLimit,
                        'app_limit' => $maxFileSize,
                    ]) }}
                </p>
            @endif

            <p class="agricart-backups__upload-hint">
                {{ __('agricart.backups.upload.direct_hint') }}
            </p>
            <p class="agricart-backups__upload-hint">
                {{ __('agricart.backups.upload.server_limit', ['size' => $effectiveUploadLimit]) }}
            </p>

            @if ($canManage)
                <form
                    id="agricart-backup-upload-form"
                    class="agricart-backups__upload-form"
                    method="post"
                    action="{{ $uploadUrl }}"
                    enctype="multipart/form-data"
                >
                    @csrf
                    <label class="agricart-backups__upload-field">
                        <span class="agricart-form-panel__label">{{ __('agricart.backups.upload.field_label') }}</span>
                        <input
                            type="file"
                            name="backup_zip"
                            id="agricart-backup-zip-input"
                            accept=".zip,application/zip"
                            required
                            @disabled($busy)
                        />
                    </label>

                    <div class="agricart-backups__actions">
                        <button type="submit" class="agricart-backups__btn is-primary" id="agricart-backup-upload-btn" @disabled($busy)>
                            {{ __('agricart.backups.actions.upload') }}
                        </button>
                    </div>
                </form>

                <div class="agricart-backups__upload-progress" id="agricart-backup-upload-progress" hidden>
                    <div class="agricart-backups__upload-progress-track" aria-hidden="true">
                        <div class="agricart-backups__upload-progress-bar" id="agricart-backup-upload-progress-bar"></div>
                    </div>
                    <p class="agricart-backups__upload-status" id="agricart-backup-upload-status" role="status">
                        {{ __('agricart.backups.upload.progress_start') }}
                    </p>
                </div>

                <p class="agricart-backups__upload-error" id="agricart-backup-upload-error" hidden role="alert"></p>
            @endif
        </div>

        <div wire:ignore>
            <script>
                (function () {
                    const form = document.getElementById('agricart-backup-upload-form');
                    if (!form || form.dataset.bound === '1') return;
                    form.dataset.bound = '1';

                    const progressWrap = document.getElementById('agricart-backup-upload-progress');
                    const progressBar = document.getElementById('agricart-backup-upload-progress-bar');
                    const statusEl = document.getElementById('agricart-backup-upload-status');
                    const errorEl = document.getElementById('agricart-backup-upload-error');
                    const submitBtn = document.getElementById('agricart-backup-upload-btn');
                    const fileInput = document.getElementById('agricart-backup-zip-input');
                    const csrf = form.querySelector('input[name="_token"]')?.value;

                    const labels = {
                        uploading: @js(__('agricart.backups.upload.progress_uploading')),
                        processing: @js(__('agricart.backups.upload.progress_processing')),
                        failed: @js(__('agricart.backups.upload.progress_failed')),
                        network: @js(__('agricart.backups.upload.progress_network')),
                    };

                    function formatBytes(bytes) {
                        if (bytes < 1024) return bytes + ' B';
                        if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
                        return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
                    }

                    function showError(message) {
                        if (!errorEl) return;
                        errorEl.textContent = message;
                        errorEl.hidden = false;
                        if (progressWrap) progressWrap.hidden = true;
                        if (submitBtn) submitBtn.disabled = false;
                    }

                    form.addEventListener('submit', function (event) {
                        event.preventDefault();
                        if (!fileInput?.files?.length) return;

                        errorEl.hidden = true;
                        progressWrap.hidden = false;
                        progressBar.style.width = '0%';
                        submitBtn.disabled = true;
                        statusEl.textContent = labels.uploading.replace(':percent', '0').replace(':loaded', '0 B').replace(':total', '…');

                        const xhr = new XMLHttpRequest();
                        xhr.open('POST', form.action);
                        xhr.setRequestHeader('X-CSRF-TOKEN', csrf || '');
                        xhr.setRequestHeader('Accept', 'application/json');
                        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');

                        xhr.upload.addEventListener('progress', function (ev) {
                            if (ev.lengthComputable) {
                                const pct = Math.min(100, Math.round((ev.loaded / ev.total) * 100));
                                progressBar.style.width = pct + '%';
                                statusEl.textContent = labels.uploading
                                    .replace(':percent', String(pct))
                                    .replace(':loaded', formatBytes(ev.loaded))
                                    .replace(':total', formatBytes(ev.total));
                            } else {
                                statusEl.textContent = labels.uploading
                                    .replace(':percent', '…')
                                    .replace(':loaded', formatBytes(ev.loaded))
                                    .replace(':total', '…');
                            }
                        });

                        xhr.addEventListener('load', function () {
                            let payload = null;
                            try {
                                payload = JSON.parse(xhr.responseText);
                            } catch (e) {
                                payload = null;
                            }

                            if (xhr.status >= 200 && xhr.status < 300) {
                                progressBar.style.width = '100%';
                                statusEl.textContent = labels.processing;
                                window.location.reload();
                                return;
                            }

                            const message = payload?.message || labels.failed + ' (HTTP ' + xhr.status + ')';
                            showError(message);
                        });

                        xhr.addEventListener('error', function () {
                            showError(labels.network);
                        });

                        xhr.send(new FormData(form));
                    });
                })();
            </script>
        </div>
    </section>

    <section class="agricart-backups__panel">
        <header class="agricart-backups__panel-head">
            <h2>{{ __('agricart.backups.sections.incoming') }}</h2>
            <p>{{ __('agricart.backups.sections.incoming_hint', ['path' => $incomingPath]) }}</p>
        </header>
        <p class="agricart-backups__upload-hint">
            {{ __('agricart.backups.incoming.large_files', ['size' => $maxFileSize]) }}
        </p>
        <code class="agricart-backups__path">{{ $incomingPath }}</code>
        @if ($canManage)
            <div class="agricart-backups__actions">
                <button type="button" class="agricart-backups__btn is-primary" wire:click="importIncoming" @disabled($busy)>
                    {{ __('agricart.backups.actions.scan_incoming') }}
                </button>
            </div>
        @endif
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

                <label class="agricart-backups__checkbox">
                    <input type="checkbox" wire:model="preserveLocalLogins" />
                    <span>{{ __('agricart.backups.restore.preserve_logins') }}</span>
                </label>
                <p class="agricart-backups__upload-hint">{{ __('agricart.backups.restore.preserve_logins_hint') }}</p>

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
