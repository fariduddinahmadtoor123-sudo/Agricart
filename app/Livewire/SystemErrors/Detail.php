<?php

namespace App\Livewire\SystemErrors;

use App\Livewire\SystemErrors\Concerns\AuthorizesSystemErrors;
use App\Models\SystemError;
use App\Services\SystemErrors\SystemErrorGuidance;
use App\Services\SystemErrors\SystemErrorReadService;
use App\Services\SystemErrors\SystemErrorReportFormatter;
use App\Services\SystemErrors\SystemLogFileReader;
use Filament\Notifications\Notification;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\On;
use Livewire\Component;

class Detail extends Component
{
    use AuthorizesSystemErrors;

    public ?SystemError $error = null;

    /** @var array{key: string, file: string, timestamp: string|null, severity: string, message: string, trace: string}|null */
    public ?array $fileEntry = null;

    public string $report = '';

    /** @var array<string, string>|null */
    public ?array $guidance = null;

    public bool $canManage = false;

    #[On('system-error-detail-open')]
    public function loadError(int $errorId, ?string $fileKey = null): void
    {
        $user = $this->authorizeSystemErrorView();
        $this->canManage = $user->isSuperAdmin() || \App\Services\Access\ModulePageAccess::can($user, 'settings', 'system-errors', 'edit');
        $this->fileEntry = null;
        $this->error = SystemError::query()
            ->with(['user', 'resolvedBy'])
            ->find($errorId);
        $this->guidance = $this->error instanceof SystemError
            ? app(SystemErrorGuidance::class)->for($this->error)
            : null;
        $this->report = $this->error instanceof SystemError
            ? app(SystemErrorReportFormatter::class)->format($this->error)
            : '';

        if ($this->error instanceof SystemError) {
            app(SystemErrorReadService::class)->markRead($this->error, $user);
            $this->dispatch('topbar-notifications-refresh');
        }
    }

    #[On('system-error-file-detail-open')]
    public function loadFileEntry(string $fileKey): void
    {
        $user = $this->authorizeSystemErrorView();
        $this->canManage = $user->isSuperAdmin() || \App\Services\Access\ModulePageAccess::can($user, 'settings', 'system-errors', 'edit');
        $this->error = null;
        $this->fileEntry = collect(app(SystemLogFileReader::class)->recent(200))
            ->firstWhere('key', $fileKey);
        $this->guidance = $this->fileEntry === null
            ? null
            : app(SystemErrorGuidance::class)->forFileEntry($this->fileEntry);
        $this->report = $this->fileEntry === null
            ? ''
            : implode(PHP_EOL, [
                'Agricart Sanitized File Log',
                'File: '.$this->fileEntry['file'],
                'Timestamp: '.($this->fileEntry['timestamp'] ?? 'unknown'),
                'Severity: '.$this->fileEntry['severity'],
                'Message: '.$this->fileEntry['message'],
                '',
                'Sanitized log entry:',
                $this->fileEntry['trace'],
            ]);
    }

    public function resolve(): void
    {
        $user = $this->authorizeSystemErrorManage();

        if (! $this->error instanceof SystemError) {
            return;
        }

        $this->error->update([
            'status' => SystemError::STATUS_RESOLVED,
            'resolved_at' => now(),
            'resolved_by_id' => $user->id,
            'resolution_type' => 'manual',
            'resolution_note' => __('agricart.system_errors.messages.manually_resolved'),
            'verified_at' => now(),
        ]);
        $this->error->refresh()->load(['user', 'resolvedBy']);
        $this->guidance = app(SystemErrorGuidance::class)->for($this->error);
        $this->report = app(SystemErrorReportFormatter::class)->format($this->error);
        $this->dispatch('system-errors-updated');

        Notification::make()
            ->success()
            ->title(__('agricart.system_errors.messages.resolved'))
            ->send();
    }

    public function reopen(): void
    {
        $this->authorizeSystemErrorManage();

        if (! $this->error instanceof SystemError) {
            return;
        }

        $this->error->update([
            'status' => SystemError::STATUS_OPEN,
            'resolved_at' => null,
            'resolved_by_id' => null,
            'resolution_type' => null,
            'resolution_note' => null,
            'verified_at' => null,
        ]);
        $this->error->refresh()->load(['user', 'resolvedBy']);
        $this->guidance = app(SystemErrorGuidance::class)->for($this->error);
        $this->report = app(SystemErrorReportFormatter::class)->format($this->error);
        $this->dispatch('system-errors-updated');

        Notification::make()
            ->success()
            ->title(__('agricart.system_errors.messages.reopened'))
            ->send();
    }

    public function render(): View
    {
        return view('livewire.system-errors.detail');
    }
}
