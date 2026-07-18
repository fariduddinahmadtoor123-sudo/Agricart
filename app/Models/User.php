<?php

namespace App\Models;

use App\Filament\Layout\ModuleChrome;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasAvatar;
use Filament\Panel;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

#[Fillable([
    'user_number',
    'name',
    'name_ur',
    'role_id',
    'status',
    'rejection_reason',
    'rejected_at',
    'email',
    'address',
    'address_ur',
    'password',
    'phone_numbers',
    'bank_accounts',
    'profile_photo_path',
    'cnic_front_path',
    'cnic_back_path',
])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements FilamentUser, HasAvatar
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_INACTIVE = 'inactive';

    public const STATUS_REJECTED = 'rejected';

    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public function canAccessPanel(Panel $panel): bool
    {
        if ($panel->getId() !== 'admin') {
            return false;
        }

        return $this->canUseAdmin();
    }

    public function canUseAdmin(): bool
    {
        if ($this->isSuperAdmin()) {
            return $this->status === self::STATUS_ACTIVE;
        }

        return $this->status === self::STATUS_ACTIVE && $this->role_id !== null;
    }

    public function isPending(): bool
    {
        return ($this->status ?? self::STATUS_ACTIVE) === self::STATUS_PENDING;
    }

    public function isRejected(): bool
    {
        return ($this->status ?? '') === self::STATUS_REJECTED;
    }

    public function loginBlockedMessage(): ?string
    {
        return match ($this->status) {
            self::STATUS_PENDING => __('agricart.auth.pending'),
            self::STATUS_REJECTED => filled($this->rejection_reason)
                ? __('agricart.auth.rejected_with_reason', ['reason' => $this->rejection_reason])
                : __('agricart.auth.rejected'),
            self::STATUS_INACTIVE => __('agricart.auth.inactive'),
            self::STATUS_ACTIVE => $this->role_id === null && ! $this->isSuperAdmin()
                ? __('agricart.auth.no_role')
                : null,
            default => __('agricart.auth.inactive'),
        };
    }

    public function isSuperAdmin(): bool
    {
        return $this->role?->isSuperAdmin() ?? false;
    }

    public function hasPermission(string $key): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        $role = $this->role;

        if ($role === null) {
            return false;
        }

        if (! $role->relationLoaded('permissions')) {
            $role->load('permissions');
        }

        return $role->permissions->contains('key', $key);
    }

    /**
     * @param  list<string>  $keys
     */
    public function hasAnyPermission(array $keys): bool
    {
        foreach ($keys as $key) {
            if ($this->hasPermission($key)) {
                return true;
            }
        }

        return false;
    }

    public function defaultAdminHomeUrl(): string
    {
        foreach (ModuleChrome::modules() as $module) {
            if (in_array($module['key'], config('agricart.modules_coming_soon', []), true)) {
                continue;
            }

            $subNavigationClass = $module['subNavigation'];
            $clusterClass = $subNavigationClass::clusterClass();

            if (! $clusterClass::canAccess()) {
                continue;
            }

            $moduleName = Str::studly($module['key']);
            $overviewClass = "App\\Filament\\Clusters\\{$moduleName}\\OverviewPage";

            if (class_exists($overviewClass) && $overviewClass::canAccess()) {
                return $overviewClass::getUrl();
            }

            $navigationUrl = $clusterClass::getNavigationUrl();

            if (filled($navigationUrl)) {
                return $navigationUrl;
            }
        }

        throw new AuthorizationException(__('agricart.access.denied'));
    }

    /**
     * @return BelongsTo<Role, $this>
     */
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    /**
     * @return array<string, string>
     */
    public function publicFileUrl(?string $path): ?string
    {
        return filled($path) ? Storage::disk('public')->url($path) : null;
    }

    public function getFilamentAvatarUrl(): ?string
    {
        return $this->publicFileUrl($this->profile_photo_path);
    }

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'rejected_at' => 'datetime',
            'password' => 'hashed',
            'phone_numbers' => 'array',
            'bank_accounts' => 'array',
        ];
    }
}
