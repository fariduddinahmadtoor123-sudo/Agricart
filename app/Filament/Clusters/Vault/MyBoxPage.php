<?php

namespace App\Filament\Clusters\Vault;

use App\Filament\Clusters\VaultCluster;
use App\Filament\Pages\BaseModulePage;
use App\Models\PersonalVaultFile;
use App\Models\PersonalVaultSecret;
use App\Models\User;
use App\Services\Vault\PersonalVaultService;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

class MyBoxPage extends BaseModulePage
{
    use WithFileUploads;

    protected static ?string $cluster = VaultCluster::class;

    protected static ?string $navigationLabel = 'My Box';

    protected static ?int $navigationSort = 1;

    protected static ?string $slug = 'my-box';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedFolderOpen;

    protected string $view = 'filament.pages.vault.my-box';

    /** @var TemporaryUploadedFile|null */
    public $upload = null;

    public string $label = '';

    public string $docType = 'other';

    public string $validFrom = '';

    public string $validUntil = '';

    public string $fileFilter = 'all';

    public string $secretTitle = '';

    public string $secretSiteUrl = '';

    public string $secretUsername = '';

    public string $secretPassword = '';

    public string $secretNotes = '';

    public ?int $editingSecretId = null;

    /** @var array<int, true> */
    public array $revealedSecretIds = [];

    public function getHeading(): string|Htmlable|null
    {
        return null;
    }

    public function getSubheading(): string|Htmlable|null
    {
        return null;
    }

    /**
     * @return array<string, string>
     */
    public function getBreadcrumbs(): array
    {
        return VaultCluster::unshiftClusterBreadcrumbs([
            static::getUrl() => static::getNavigationLabel(),
        ]);
    }

    public function boxOwner(): User
    {
        $user = auth()->user();
        abort_unless($user instanceof User, 403);

        return $user;
    }

    /**
     * @return array{used: int, quota: int, remaining: int, files: int, max_files: int, used_display: string, quota_display: string, percent: float}
     */
    public function usage(): array
    {
        return app(PersonalVaultService::class)->usageSummary($this->boxOwner());
    }

    /**
     * @return Collection<int, PersonalVaultFile>
     */
    public function files(): Collection
    {
        return app(PersonalVaultService::class)->filesForOwner($this->boxOwner(), $this->fileFilter);
    }

    /**
     * @return array{renew_soon: int, expired: int, renewable: int, days: int, total_alerts: int}
     */
    public function renewalReminders(): array
    {
        return app(PersonalVaultService::class)->renewalReminderSummary($this->boxOwner());
    }

    public function setFileFilter(string $filter): void
    {
        $this->fileFilter = app(PersonalVaultService::class)->normalizeFileFilter($filter);
    }

    public function saveUpload(): void
    {
        $this->authorizeCreate();

        $this->validate([
            'upload' => ['required', 'file', 'max:'.((int) config('agricart.personal_vault.max_file_mb', 8) * 1024)],
            'label' => ['nullable', 'string', 'max:191'],
            'docType' => ['required', 'string', 'in:'.implode(',', PersonalVaultFile::DOC_TYPES)],
            'validFrom' => ['nullable', 'date'],
            'validUntil' => ['nullable', 'date', 'after_or_equal:validFrom'],
        ]);

        try {
            app(PersonalVaultService::class)->store(
                $this->boxOwner(),
                $this->upload,
                $this->label !== '' ? $this->label : null,
                $this->docType,
                $this->validFrom !== '' ? $this->validFrom : null,
                $this->validUntil !== '' ? $this->validUntil : null,
            );
        } catch (ValidationException $e) {
            $message = collect($e->errors())->flatten()->first() ?: __('agricart.pages.vault.upload_failed');
            Notification::make()->title((string) $message)->danger()->send();

            return;
        }

        $this->reset('upload', 'label', 'validFrom', 'validUntil');
        $this->docType = 'other';
        Notification::make()
            ->title(__('agricart.pages.vault.upload_ok'))
            ->success()
            ->send();
    }

    public function deleteFile(int $id): void
    {
        $this->authorizeCreate();

        $actor = $this->boxOwner();
        $file = PersonalVaultFile::query()
            ->where('user_id', $actor->id)
            ->whereKey($id)
            ->firstOrFail();

        app(PersonalVaultService::class)->assertCanManage($actor, $file);
        app(PersonalVaultService::class)->delete($file);

        Notification::make()
            ->title(__('agricart.pages.vault.delete_ok'))
            ->success()
            ->send();
    }

    /**
     * @return Collection<int, PersonalVaultSecret>
     */
    public function secrets(): Collection
    {
        return PersonalVaultSecret::query()
            ->where('user_id', $this->boxOwner()->id)
            ->latest('id')
            ->limit(200)
            ->get();
    }

    public function startEditSecret(int $id): void
    {
        $this->authorizeCreate();
        $actor = $this->boxOwner();
        $secret = PersonalVaultSecret::query()
            ->where('user_id', $actor->id)
            ->whereKey($id)
            ->firstOrFail();

        app(PersonalVaultService::class)->assertCanManage($actor, $secret);
        $this->editingSecretId = (int) $secret->id;
        $this->secretTitle = (string) $secret->title;
        $this->secretSiteUrl = (string) ($secret->site_url ?? '');
        $this->secretUsername = (string) ($secret->username ?? '');
        $this->secretPassword = '';
        $this->secretNotes = (string) ($secret->notes ?? '');
    }

    public function cancelEditSecret(): void
    {
        $this->resetSecretForm();
    }

    public function saveSecret(): void
    {
        $this->authorizeCreate();

        $this->validate([
            'secretTitle' => ['required', 'string', 'max:191'],
            'secretSiteUrl' => ['nullable', 'string', 'max:500'],
            'secretUsername' => ['nullable', 'string', 'max:255'],
            'secretPassword' => [$this->editingSecretId ? 'nullable' : 'required', 'string', 'max:500'],
            'secretNotes' => ['nullable', 'string', 'max:5000'],
        ]);

        $payload = [
            'title' => $this->secretTitle,
            'site_url' => $this->secretSiteUrl,
            'username' => $this->secretUsername,
            'password' => $this->secretPassword,
            'notes' => $this->secretNotes,
        ];

        try {
            $svc = app(PersonalVaultService::class);
            if ($this->editingSecretId) {
                $secret = PersonalVaultSecret::query()
                    ->where('user_id', $this->boxOwner()->id)
                    ->whereKey($this->editingSecretId)
                    ->firstOrFail();
                $svc->assertCanManage($this->boxOwner(), $secret);
                $svc->updateSecret($secret, $payload);
            } else {
                $svc->storeSecret($this->boxOwner(), $payload);
            }
        } catch (ValidationException $e) {
            $message = collect($e->errors())->flatten()->first() ?: __('agricart.pages.vault.secret_failed');
            Notification::make()->title((string) $message)->danger()->send();

            return;
        }

        $this->resetSecretForm();
        Notification::make()
            ->title(__('agricart.pages.vault.secret_ok'))
            ->success()
            ->send();
    }

    public function deleteSecret(int $id): void
    {
        $this->authorizeCreate();
        $actor = $this->boxOwner();
        $secret = PersonalVaultSecret::query()
            ->where('user_id', $actor->id)
            ->whereKey($id)
            ->firstOrFail();

        app(PersonalVaultService::class)->assertCanManage($actor, $secret);
        app(PersonalVaultService::class)->deleteSecret($secret);
        unset($this->revealedSecretIds[$id]);

        if ($this->editingSecretId === $id) {
            $this->resetSecretForm();
        }

        Notification::make()
            ->title(__('agricart.pages.vault.secret_deleted'))
            ->success()
            ->send();
    }

    public function toggleRevealSecret(int $id): void
    {
        $actor = $this->boxOwner();
        $secret = PersonalVaultSecret::query()
            ->where('user_id', $actor->id)
            ->whereKey($id)
            ->firstOrFail();
        app(PersonalVaultService::class)->assertCanManage($actor, $secret);

        if (isset($this->revealedSecretIds[$id])) {
            unset($this->revealedSecretIds[$id]);
        } else {
            $this->revealedSecretIds[$id] = true;
        }
    }

    protected function resetSecretForm(): void
    {
        $this->editingSecretId = null;
        $this->secretTitle = '';
        $this->secretSiteUrl = '';
        $this->secretUsername = '';
        $this->secretPassword = '';
        $this->secretNotes = '';
    }

    protected function authorizeCreate(): void
    {
        $user = auth()->user();
        abort_unless(
            $user instanceof User && (
                $user->isSuperAdmin()
                || \App\Services\Access\ModulePageAccess::can($user, 'vault', 'my-box', 'create')
                || \App\Services\Access\ModulePageAccess::can($user, 'vault', 'my-box', 'edit')
            ),
            403
        );
    }
}
