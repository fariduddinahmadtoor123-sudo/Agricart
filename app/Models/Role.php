<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Role extends Model
{
    /**
     * Runtime-only permission keys from EagerLoadUserRole cache (never persisted).
     *
     * @var list<string>|null
     */
    private ?array $runtimePermissionKeys = null;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'slug',
        'description',
        'is_system',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_system' => 'boolean',
        ];
    }

    /**
     * @return BelongsToMany<Permission, $this>
     */
    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class);
    }

    /**
     * @return HasMany<\App\Models\User, $this>
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function isSuperAdmin(): bool
    {
        return $this->slug === 'super-admin';
    }

    /**
     * @param  list<string>  $keys
     */
    public function setPermissionKeys(array $keys): void
    {
        $this->runtimePermissionKeys = array_values($keys);
    }

    /**
     * @return list<string>|null
     */
    public function permissionKeys(): ?array
    {
        return $this->runtimePermissionKeys;
    }
}
