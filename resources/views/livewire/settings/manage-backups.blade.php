<div class="agricart-backups" @if($busy) wire:poll.2s @endif>
    @if ($flashMessage)
        <div
            wire:loading.remove
            wire:target="createFull,createDatabase,importIncoming,confirmRestore"
            @class([
                'agricart-backups__alert',
                'is-success' => $flashType === 'success',
                'is-danger' => $flashType === 'danger',
                'is-warning' => $flashType === 'warning',
            ])
        >
            {{ $flashMessage }}
        </div>
    @endif

    {{-- Client-side loading: shows as soon as the Livewire request starts (busy alone never appears during create). --}}
    <div
        wire:loading.flex
        wire:target="createFull,createDatabase,importIncoming,confirmRestore"
        class="agricart-backups__progress"
        role="status"
        aria-live="assertive"
        aria-busy="true"
    >
        <span class="agricart-backups__spinner" aria-hidden="true"></span>
        <div class="agricart-backups__progress-copy">
            <p wire:loading wire:target="createFull">{{ __('agricart.backups.status.creating_full') }}</p>
            <p wire:loading wire:target="createDatabase">{{ __('agricart.backups.status.creating_database') }}</p>
            <p wire:loading wire:target="importIncoming">{{ __('agricart.backups.status.importing') }}</p>
            <p wire:loading wire:target="confirmRestore">{{ __('agricart.backups.status.restoring') }}</p>
        </div>
    </div>

    @if ($busy)
        <div class="agricart-backups__progress" role="status" aria-live="polite">
            <span class="agricart-backups__spinner" aria-hidden="true"></span>
            <p>{{ __('agricart.backups.status.working') }}</p>
        </div>
    @endif

    @if ($databaseDriver === 'sqlite')
        <div class="agricart-backups__alert is-warning">
            {{ __('agricart.backups.compatibility.sqlite_warning') }}
        </div>
    @endif

    <section class="agricart-backups__panel">
        <header class="agricart-backups__panel-head">
            <h2>{{ __('agricart.backups.sections.create') }}</h2>
            <p class="agricart-backups__lede">{{ __('agricart.backups.sections.create_hint') }}</p>
        </header>

        <div class="agricart-backups__actions agricart-backups__actions--flush">
            <button
                type="button"
                class="agricart-backups__btn is-primary"
                wire:click="createFull"
                wire:loading.attr="disabled"
                wire:target="createFull,createDatabase,importIncoming,confirmRestore"
                @disabled($busy || ! $canManage)
            >
                <span wire:loading.remove wire:target="createFull">{{ __('agricart.backups.actions.create_full') }}</span>
                <span wire:loading.inline-flex wire:target="createFull" class="agricart-backups__btn-state">
                    <span class="agricart-backups__spinner" aria-hidden="true"></span>
                    {{ __('agricart.backups.actions.creating_full') }}
                </span>
            </button>
            <button
                type="button"
                class="agricart-backups__btn"
                wire:click="createDatabase"
                wire:loading.attr="disabled"
                wire:target="createFull,createDatabase,importIncoming,confirmRestore"
                @disabled($busy || ! $canManage)
            >
                <span wire:loading.remove wire:target="createDatabase">{{ __('agricart.backups.actions.create_database') }}</span>
                <span wire:loading.inline-flex wire:target="createDatabase" class="agricart-backups__btn-state">
                    <span class="agricart-backups__spinner" aria-hidden="true"></span>
                    {{ __('agricart.backups.actions.creating_database') }}
                </span>
            </button>
        </div>
    </section>

    <section class="agricart-backups__panel">
        <header class="agricart-backups__panel-head">
            <h2>{{ __('agricart.backups.sections.schedule') }}</h2>
            <p class="agricart-backups__lede">{{ __('agricart.backups.sections.schedule_hint') }}</p>
        </header>

        <p class="agricart-backups__auto">
            <span>{{ __('agricart.backups.schedule.enabled_label') }}</span>
            {{ $scheduleSummary }}
        </p>

        <div class="agricart-backups__schedule-grid">
            <label class="agricart-backups__checkbox">
                <input type="checkbox" wire:model="scheduleEnabled" @disabled(! $canManage || $busy)>
                <span>{{ __('agricart.backups.schedule.field_enabled') }}</span>
            </label>

            <label class="agricart-backups__field">
                <span>{{ __('agricart.backups.schedule.field_type') }}</span>
                <select wire:model.live="scheduleType" class="agricart-backups__input" @disabled(! $canManage || $busy)>
                    <option value="full">{{ __('agricart.backups.types.full') }} — {{ __('agricart.backups.schedule.type_full_recommended') }}</option>
                    <option value="database">{{ __('agricart.backups.types.database') }}</option>
                </select>
            </label>

            <label class="agricart-backups__field">
                <span>{{ __('agricart.backups.schedule.field_frequency') }}</span>
                <select wire:model.live="scheduleFrequency" class="agricart-backups__input" @disabled(! $canManage || $busy)>
                    <option value="daily">{{ __('agricart.backups.schedule.frequency_daily') }}</option>
                    <option value="every_2_days">{{ __('agricart.backups.schedule.frequency_every_2_days') }}</option>
                    <option value="every_3_days">{{ __('agricart.backups.schedule.frequency_every_3_days') }}</option>
                    <option value="weekly">{{ __('agricart.backups.schedule.frequency_weekly') }}</option>
                </select>
            </label>

            <label class="agricart-backups__field">
                <span>{{ __('agricart.backups.schedule.field_time') }}</span>
                <input type="time" wire:model="scheduleTime" class="agricart-backups__input" @disabled(! $canManage || $busy)>
            </label>

            @if ($scheduleFrequency === 'weekly')
                <label class="agricart-backups__field">
                    <span>{{ __('agricart.backups.schedule.field_weekday') }}</span>
                    <select wire:model="scheduleWeekday" class="agricart-backups__input" @disabled(! $canManage || $busy)>
                        <option value="0">{{ __('agricart.backups.schedule.sunday') }}</option>
                        <option value="1">{{ __('agricart.backups.schedule.monday') }}</option>
                        <option value="2">{{ __('agricart.backups.schedule.tuesday') }}</option>
                        <option value="3">{{ __('agricart.backups.schedule.wednesday') }}</option>
                        <option value="4">{{ __('agricart.backups.schedule.thursday') }}</option>
                        <option value="5">{{ __('agricart.backups.schedule.friday') }}</option>
                        <option value="6">{{ __('agricart.backups.schedule.saturday') }}</option>
                    </select>
                </label>
            @endif

            <label class="agricart-backups__checkbox agricart-backups__checkbox--block">
                <input type="checkbox" wire:model.live="scheduleDatabaseDailyEnabled" @disabled(! $canManage || $busy || $scheduleType === 'database')>
                <span>{{ __('agricart.backups.schedule.field_database_daily') }}</span>
            </label>

            @if ($scheduleDatabaseDailyEnabled && $scheduleType !== 'database')
                <label class="agricart-backups__field">
                    <span>{{ __('agricart.backups.schedule.field_database_daily_time') }}</span>
                    <input type="time" wire:model="scheduleDatabaseDailyTime" class="agricart-backups__input" @disabled(! $canManage || $busy)>
                </label>
            @endif
        </div>

        <div class="agricart-backups__actions agricart-backups__actions--flush">
            <button type="button" class="agricart-backups__btn is-primary" wire:click="saveSchedule" @disabled($busy || ! $canManage)>
                {{ __('agricart.backups.actions.save_schedule') }}
            </button>
        </div>

        <div class="agricart-backups__cron">
            <p class="agricart-backups__lede">{{ __('agricart.backups.schedule.cron_intro') }}</p>
            <code class="agricart-backups__path" id="agricart-backup-cron-line">{{ $hostCronCommand }}</code>
            <p class="agricart-backups__meta">
                @if ($schedulerConfigured)
                    {{ __('agricart.backups.schedule.cron_marked_ok') }}
                @else
                    {{ __('agricart.backups.schedule.cron_marked_check') }}
                @endif
            </p>
        </div>
    </section>

    <section class="agricart-backups__panel">
        <header class="agricart-backups__panel-head">
            <h2>{{ __('agricart.backups.sections.upload') }}</h2>
            <p class="agricart-backups__lede">{{ __('agricart.backups.sections.upload_hint') }}</p>
        </header>

        <div class="agricart-backups__upload" id="agricart-backup-upload" data-upload-url="{{ $uploadUrl }}">
            @if ($canManage)
                <form
                    id="agricart-backup-upload-form"
                    class="agricart-backups__upload-form"
                    method="post"
                    action="{{ $uploadUrl }}"
                    enctype="multipart/form-data"
                >
                    @csrf
                    <label class="agricart-backups__dropzone" for="agricart-backup-zip-input">
                        <span class="agricart-backups__dropzone-title">{{ __('agricart.backups.upload.dropzone_title') }}</span>
                        <span class="agricart-backups__dropzone-hint" id="agricart-backup-zip-name">{{ __('agricart.backups.upload.dropzone_empty') }}</span>
                        <input
                            type="file"
                            name="backup_zip"
                            id="agricart-backup-zip-input"
                            class="agricart-backups__file-input"
                            accept=".zip,application/zip"
                            required
                            @disabled($busy)
                        />
                    </label>

                    <div class="agricart-backups__actions agricart-backups__actions--flush">
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
                    const fileNameEl = document.getElementById('agricart-backup-zip-name');
                    const csrf = form.querySelector('input[name="_token"]')?.value;
                    const emptyLabel = @js(__('agricart.backups.upload.dropzone_empty'));
                    const selectedLabel = @js(__('agricart.backups.upload.dropzone_selected'));

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

                    fileInput?.addEventListener('change', function () {
                        if (!fileNameEl) return;
                        const file = fileInput.files?.[0];
                        fileNameEl.textContent = file
                            ? selectedLabel.replace(':name', file.name)
                            : emptyLabel;
                    });

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

    <section class="agricart-backups__panel agricart-backups__panel--compact">
        <header class="agricart-backups__panel-head agricart-backups__panel-head--inline">
            <h2>{{ __('agricart.backups.sections.export') }}</h2>
        </header>

        <div class="agricart-backups__actions agricart-backups__actions--flush">
            <a href="{{ $productsExportUrl }}" class="agricart-backups__btn" @class(['is-disabled' => ! $canManage]) @if(! $canManage) aria-disabled="true" @endif>
                {{ __('agricart.backups.actions.export_products') }}
            </a>
            <a href="{{ $categoriesExportUrl }}" class="agricart-backups__btn" @class(['is-disabled' => ! $canManage]) @if(! $canManage) aria-disabled="true" @endif>
                {{ __('agricart.backups.actions.export_categories') }}
            </a>
        </div>
    </section>

    <section class="agricart-backups__panel">
        <header class="agricart-backups__panel-head agricart-backups__panel-head--inline">
            <h2>{{ __('agricart.backups.sections.list') }}</h2>
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
                            <th>{{ __('agricart.backups.list.col_source') }}</th>
                            <th>{{ __('agricart.backups.list.col_size') }}</th>
                            <th>{{ __('agricart.backups.list.col_files') }}</th>
                            <th>{{ __('agricart.backups.list.col_actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($backups as $backup)
                            <tr wire:key="backup-{{ $backup->id }}">
                                <td>{{ agricart_datetime($backup->createdAt) }}</td>
                                <td>{{ __('agricart.backups.types.' . $backup->type) }}</td>
                                <td>{{ __('agricart.backups.sources.' . ($backup->meta['source'] ?? 'manual')) }}</td>
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
        <div
            class="agricart-staff-approvals__modal"
            x-data="agricartBackupRestoreProgress(@js([
                'statusUrl' => route('admin.backups.restore-progress'),
                'labels' => __('agricart.backups.restore.steps'),
            ]))"
            @unless ($busy)
                wire:keydown.escape.window="cancelRestore"
            @endunless
        >
            <div
                class="agricart-staff-approvals__modal-backdrop"
                @unless ($busy)
                    wire:click="cancelRestore"
                @endunless
            ></div>
            <div
                class="agricart-staff-approvals__modal-card agricart-staff-approvals__modal-card--wide agricart-backups__restore-card"
                role="dialog"
                aria-modal="true"
                wire:loading.class="is-restoring"
                wire:target="confirmRestore"
            >
                <div wire:loading.remove wire:target="confirmRestore">
                    <h3 class="agricart-staff-approvals__modal-title">{{ __('agricart.backups.restore.title') }}</h3>
                    <p class="agricart-backups__restore-warning">{{ __('agricart.backups.restore.warning') }}</p>

                    <label class="agricart-backups__checkbox">
                        <input type="checkbox" wire:model="restoreSafetyBackup" @disabled($busy) />
                        <span>{{ __('agricart.backups.restore.safety_backup') }}</span>
                    </label>

                    <label class="agricart-backups__checkbox">
                        <input type="checkbox" wire:model="preserveLocalLogins" @disabled($busy) />
                        <span>{{ __('agricart.backups.restore.preserve_logins') }}</span>
                    </label>

                    <label class="agricart-form-panel__label" for="backup-restore-confirm">{{ __('agricart.backups.restore.confirm_label') }}</label>
                    <input id="backup-restore-confirm" type="text" wire:model="restoreConfirm" class="agricart-backups__input" placeholder="RESTORE" @disabled($busy) />

                    <div class="agricart-staff-approvals__modal-actions">
                        <button type="button" class="agricart-categories__action" wire:click="cancelRestore" @disabled($busy) wire:loading.attr="disabled" wire:target="confirmRestore">
                            {{ __('agricart.backups.actions.cancel') }}
                        </button>
                        <button type="button" class="agricart-categories__action is-danger" x-on:click="startRestore($wire)" @disabled($busy) wire:loading.attr="disabled" wire:target="confirmRestore">
                            {{ __('agricart.backups.actions.confirm_restore') }}
                        </button>
                    </div>
                </div>

                <div class="agricart-backups__restore-working" wire:loading.flex wire:target="confirmRestore" role="status" aria-live="polite">
                    <span class="agricart-backups__spinner" aria-hidden="true"></span>
                    <div>
                        <p class="agricart-backups__restore-working-title">{{ __('agricart.backups.restore.working_title') }}</p>
                        <p class="agricart-backups__restore-elapsed">
                            {{ __('agricart.backups.restore.elapsed') }}
                            <span x-text="formatElapsed()"></span>
                        </p>
                        <p class="agricart-backups__restore-step" x-text="stepLabel()"></p>
                        <p class="agricart-backups__restore-working-text">{{ __('agricart.backups.restore.working_text') }}</p>
                        <p class="agricart-backups__restore-hint" x-show="status === 'done'" x-cloak>{{ __('agricart.backups.restore.done_hint') }}</p>
                        <p class="agricart-backups__restore-hint is-danger" x-show="status === 'failed'" x-cloak>{{ __('agricart.backups.restore.failed_hint') }}</p>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>

@script
<script>
    Alpine.data('agricartBackupRestoreProgress', (config) => ({
        statusUrl: config.statusUrl,
        labels: config.labels || {},
        token: '',
        startedAt: null,
        now: Date.now(),
        step: 'starting',
        status: 'running',
        timer: null,
        poller: null,

        init() {
            this.token = (window.crypto && window.crypto.randomUUID)
                ? window.crypto.randomUUID()
                : 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, (c) => {
                    const r = Math.random() * 16 | 0;
                    const v = c === 'x' ? r : (r & 0x3 | 0x8);
                    return v.toString(16);
                });
        },

        startRestore(wire) {
            this.startedAt = Date.now();
            this.now = this.startedAt;
            this.step = 'starting';
            this.status = 'running';
            this.startTimers();
            wire.confirmRestore(this.token);
        },

        startTimers() {
            this.stopTimers();
            this.timer = setInterval(() => {
                this.now = Date.now();
            }, 1000);
            this.poller = setInterval(() => {
                this.poll();
            }, 2000);
            this.poll();
        },

        stopTimers() {
            if (this.timer) {
                clearInterval(this.timer);
                this.timer = null;
            }
            if (this.poller) {
                clearInterval(this.poller);
                this.poller = null;
            }
        },

        destroy() {
            this.stopTimers();
        },

        formatElapsed() {
            if (! this.startedAt) {
                return '00:00';
            }

            let seconds = Math.max(0, Math.floor((this.now - this.startedAt) / 1000));
            const hours = Math.floor(seconds / 3600);
            seconds = seconds % 3600;
            const minutes = Math.floor(seconds / 60);
            const rest = seconds % 60;
            const pad = (value) => String(value).padStart(2, '0');

            if (hours > 0) {
                return hours + ':' + pad(minutes) + ':' + pad(rest);
            }

            return pad(minutes) + ':' + pad(rest);
        },

        stepLabel() {
            return this.labels[this.step] || this.labels.starting || '';
        },

        async poll() {
            if (this.status === 'done' || this.status === 'failed') {
                if (this.poller) {
                    clearInterval(this.poller);
                    this.poller = null;
                }

                return;
            }

            try {
                const joiner = this.statusUrl.indexOf('?') === -1 ? '?' : '&';
                const response = await fetch(this.statusUrl + joiner + 'token=' + encodeURIComponent(this.token), {
                    headers: { Accept: 'application/json' },
                    credentials: 'same-origin',
                });

                if (! response.ok) {
                    return;
                }

                const data = await response.json();

                if (! data.active) {
                    return;
                }

                if (data.step) {
                    this.step = data.step;
                }

                if (data.status) {
                    this.status = data.status;
                }
            } catch (error) {
                // Keep the elapsed timer running if the poll fails (maintenance / network).
            }
        },
    }));
</script>
@endscript
