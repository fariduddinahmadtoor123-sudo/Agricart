<?php

namespace App\Services\Vault;

use App\Models\PersonalVaultFile;
use App\Models\PersonalVaultQuota;
use App\Models\PersonalVaultSecret;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PersonalVaultService
{
    public function disk(): string
    {
        return 'local';
    }

    public function defaultQuotaBytes(): int
    {
        $mb = (int) config('agricart.personal_vault.default_quota_mb', 100);

        return max(1, $mb) * 1024 * 1024;
    }

    public function maxFileBytes(): int
    {
        $mb = (int) config('agricart.personal_vault.max_file_mb', 8);

        return max(1, $mb) * 1024 * 1024;
    }

    public function maxFilesPerUser(): int
    {
        return max(1, (int) config('agricart.personal_vault.max_files', 50));
    }

    public function quotaBytesFor(User $user): int
    {
        $override = PersonalVaultQuota::query()->where('user_id', $user->id)->value('quota_bytes');

        return $override !== null ? (int) $override : $this->defaultQuotaBytes();
    }

    public function usedBytesFor(int $userId): int
    {
        return (int) PersonalVaultFile::query()->where('user_id', $userId)->sum('size_bytes');
    }

    public function fileCountFor(int $userId): int
    {
        return PersonalVaultFile::query()->where('user_id', $userId)->count();
    }

    /**
     * @return array{used: int, quota: int, remaining: int, files: int, max_files: int, used_display: string, quota_display: string, percent: float}
     */
    public function usageSummary(User $user): array
    {
        $used = $this->usedBytesFor((int) $user->id);
        $quota = $this->quotaBytesFor($user);
        $remaining = max(0, $quota - $used);
        $percent = $quota > 0 ? min(100, round(($used / $quota) * 100, 1)) : 0.0;

        return [
            'used' => $used,
            'quota' => $quota,
            'remaining' => $remaining,
            'files' => $this->fileCountFor((int) $user->id),
            'max_files' => $this->maxFilesPerUser(),
            'used_display' => $this->formatBytes($used),
            'quota_display' => $this->formatBytes($quota),
            'percent' => $percent,
        ];
    }

    public function formatBytes(int $bytes): string
    {
        if ($bytes < 1024) {
            return $bytes.' B';
        }
        if ($bytes < 1024 * 1024) {
            return rtrim(rtrim(number_format($bytes / 1024, 1, '.', ''), '0'), '.').' KB';
        }

        return rtrim(rtrim(number_format($bytes / (1024 * 1024), 2, '.', ''), '0'), '.').' MB';
    }

    public function maxSecretsPerUser(): int
    {
        return max(1, (int) config('agricart.personal_vault.max_secrets', 100));
    }

    public function secretCountFor(int $userId): int
    {
        return PersonalVaultSecret::query()->where('user_id', $userId)->count();
    }

    public function assertCanManage(User $actor, PersonalVaultFile|PersonalVaultSecret $item): void
    {
        if ($actor->isSuperAdmin()) {
            return;
        }

        abort_unless((int) $item->user_id === (int) $actor->id, 403);
    }

    public function assertCanViewBox(User $actor, int $ownerId): void
    {
        if ($actor->isSuperAdmin()) {
            return;
        }

        abort_unless((int) $actor->id === $ownerId, 403);
    }

    public function normalizeDocType(?string $docType): string
    {
        $docType = trim((string) $docType);

        return in_array($docType, PersonalVaultFile::DOC_TYPES, true) ? $docType : 'other';
    }

    public function normalizeFileFilter(?string $filter): string
    {
        $filter = trim((string) $filter);

        return in_array($filter, PersonalVaultFile::FILE_FILTERS, true) ? $filter : 'all';
    }

    /**
     * @return Collection<int, PersonalVaultFile>
     */
    public function filesForOwner(User $owner, string $filter = 'all', int $limit = 200): \Illuminate\Support\Collection
    {
        $filter = $this->normalizeFileFilter($filter);

        return PersonalVaultFile::query()
            ->where('user_id', $owner->id)
            ->applyFileFilter($filter)
            ->limit($limit)
            ->get();
    }

    /**
     * @return array{renew_soon: int, expired: int, renewable: int, days: int, total_alerts: int}
     */
    public function renewalReminderSummary(User $owner): array
    {
        $base = PersonalVaultFile::query()->where('user_id', $owner->id);
        $renewSoon = (clone $base)->renewSoon()->count();
        $expired = (clone $base)->expired()->count();
        $renewable = (clone $base)->renewableTypes()->count();

        return [
            'renew_soon' => $renewSoon,
            'expired' => $expired,
            'renewable' => $renewable,
            'days' => PersonalVaultFile::reminderDays(),
            'total_alerts' => $renewSoon + $expired,
        ];
    }

    public function normalizeDate(?string $date): ?string
    {
        $date = trim((string) $date);
        if ($date === '') {
            return null;
        }

        try {
            return \Carbon\Carbon::parse($date)->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * @param  list<string>  $allowedMimes
     */
    public function store(
        User $owner,
        UploadedFile $upload,
        ?string $label = null,
        ?string $docType = null,
        ?string $validFrom = null,
        ?string $validUntil = null,
    ): PersonalVaultFile {

        $size = (int) $upload->getSize();
        if ($size <= 0) {
            throw ValidationException::withMessages([
                'upload' => __('agricart.pages.vault.upload_empty'),
            ]);
        }

        if ($size > $this->maxFileBytes()) {
            throw ValidationException::withMessages([
                'upload' => __('agricart.pages.vault.upload_too_large', [
                    'max' => $this->formatBytes($this->maxFileBytes()),
                ]),
            ]);
        }

        if ($this->fileCountFor((int) $owner->id) >= $this->maxFilesPerUser()) {
            throw ValidationException::withMessages([
                'upload' => __('agricart.pages.vault.upload_file_limit', [
                    'max' => $this->maxFilesPerUser(),
                ]),
            ]);
        }

        $used = $this->usedBytesFor((int) $owner->id);
        $quota = $this->quotaBytesFor($owner);
        if (($used + $size) > $quota) {
            throw ValidationException::withMessages([
                'upload' => __('agricart.pages.vault.upload_quota', [
                    'remaining' => $this->formatBytes(max(0, $quota - $used)),
                ]),
            ]);
        }

        $allowed = config('agricart.personal_vault.allowed_mimes', []);
        $mime = (string) ($upload->getMimeType() ?: 'application/octet-stream');
        if (is_array($allowed) && $allowed !== [] && ! in_array($mime, $allowed, true)) {
            $ext = strtolower((string) $upload->getClientOriginalExtension());
            $allowedExt = config('agricart.personal_vault.allowed_extensions', []);
            if (! is_array($allowedExt) || ! in_array($ext, $allowedExt, true)) {
                throw ValidationException::withMessages([
                    'upload' => __('agricart.pages.vault.upload_type'),
                ]);
            }
        }

        $original = $upload->getClientOriginalName() ?: 'file';
        $safeBase = Str::slug(pathinfo($original, PATHINFO_FILENAME)) ?: 'file';
        $ext = strtolower((string) $upload->getClientOriginalExtension());
        $stored = $safeBase.'-'.Str::lower(Str::random(8)).($ext !== '' ? '.'.$ext : '');
        $directory = 'personal-vault/'.$owner->id;
        $path = $upload->storeAs($directory, $stored, $this->disk());

        if (! is_string($path) || $path === '') {
            throw ValidationException::withMessages([
                'upload' => __('agricart.pages.vault.upload_failed'),
            ]);
        }

        $from = $this->normalizeDate($validFrom);
        $until = $this->normalizeDate($validUntil);

        if ($from !== null && $until !== null && $until < $from) {
            throw ValidationException::withMessages([
                'validUntil' => __('agricart.pages.vault.valid_range_invalid'),
            ]);
        }

        return PersonalVaultFile::query()->create([
            'user_id' => $owner->id,
            'label' => filled(trim((string) $label)) ? trim((string) $label) : null,
            'doc_type' => $this->normalizeDocType($docType),
            'valid_from' => $from,
            'valid_until' => $until,
            'original_name' => Str::limit($original, 240, ''),
            'path' => $path,
            'mime' => $mime,
            'size_bytes' => $size,
        ]);
    }

    public function delete(PersonalVaultFile $file): void
    {
        DB::transaction(function () use ($file): void {
            $path = (string) $file->path;
            $file->delete();
            if ($path !== '' && Storage::disk($this->disk())->exists($path)) {
                Storage::disk($this->disk())->delete($path);
            }
        });
    }

    public function setQuota(User $user, int $quotaBytes): PersonalVaultQuota
    {
        return PersonalVaultQuota::query()->updateOrCreate(
            ['user_id' => $user->id],
            ['quota_bytes' => max(1024 * 1024, $quotaBytes)],
        );
    }

    /**
     * @param  array{title: string, site_url?: string|null, username?: string|null, password: string, notes?: string|null}  $data
     */
    public function storeSecret(User $owner, array $data): PersonalVaultSecret
    {
        $title = trim((string) ($data['title'] ?? ''));
        $password = (string) ($data['password'] ?? '');

        if ($title === '') {
            throw ValidationException::withMessages([
                'secretTitle' => __('agricart.pages.vault.secret_title_required'),
            ]);
        }

        if ($password === '') {
            throw ValidationException::withMessages([
                'secretPassword' => __('agricart.pages.vault.secret_password_required'),
            ]);
        }

        if ($this->secretCountFor((int) $owner->id) >= $this->maxSecretsPerUser()) {
            throw ValidationException::withMessages([
                'secretTitle' => __('agricart.pages.vault.secret_limit', [
                    'max' => $this->maxSecretsPerUser(),
                ]),
            ]);
        }

        $url = trim((string) ($data['site_url'] ?? ''));
        if ($url !== '' && ! filter_var($url, FILTER_VALIDATE_URL) && ! preg_match('#^[\w.-]+\.[a-z]{2,}#i', $url)) {
            // Allow hostnames without scheme; store as typed.
            $url = Str::limit($url, 500, '');
        }

        return PersonalVaultSecret::query()->create([
            'user_id' => $owner->id,
            'title' => Str::limit($title, 191, ''),
            'site_url' => $url !== '' ? Str::limit($url, 500, '') : null,
            'username' => filled(trim((string) ($data['username'] ?? '')))
                ? Str::limit(trim((string) $data['username']), 255, '')
                : null,
            'password' => $password,
            'notes' => filled(trim((string) ($data['notes'] ?? '')))
                ? trim((string) $data['notes'])
                : null,
        ]);
    }

    /**
     * @param  array{title: string, site_url?: string|null, username?: string|null, password?: string|null, notes?: string|null}  $data
     */
    public function updateSecret(PersonalVaultSecret $secret, array $data): PersonalVaultSecret
    {
        $title = trim((string) ($data['title'] ?? ''));
        if ($title === '') {
            throw ValidationException::withMessages([
                'secretTitle' => __('agricart.pages.vault.secret_title_required'),
            ]);
        }

        $url = trim((string) ($data['site_url'] ?? ''));
        $payload = [
            'title' => Str::limit($title, 191, ''),
            'site_url' => $url !== '' ? Str::limit($url, 500, '') : null,
            'username' => filled(trim((string) ($data['username'] ?? '')))
                ? Str::limit(trim((string) $data['username']), 255, '')
                : null,
            'notes' => filled(trim((string) ($data['notes'] ?? '')))
                ? trim((string) $data['notes'])
                : null,
        ];

        $password = (string) ($data['password'] ?? '');
        if ($password !== '') {
            $payload['password'] = $password;
        }

        $secret->update($payload);

        return $secret->fresh() ?? $secret;
    }

    public function deleteSecret(PersonalVaultSecret $secret): void
    {
        $secret->delete();
    }
}
