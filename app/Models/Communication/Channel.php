<?php

namespace App\Models\Communication;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Channel extends Model
{
    protected $table = 'comm_channels';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'key',
        'name',
        'is_active',
        'allowed_actor_types',
        'allows_groups',
        'allows_attachments',
        'allowed_mime_groups',
        'allows_tasks',
        'visibility',
        'settings',
        'sort_order',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'allowed_actor_types' => 'array',
            'allows_groups' => 'boolean',
            'allows_attachments' => 'boolean',
            'allowed_mime_groups' => 'array',
            'allows_tasks' => 'boolean',
            'settings' => 'array',
        ];
    }

    public function conversations(): HasMany
    {
        return $this->hasMany(Conversation::class, 'channel_id');
    }

    public function allowsActorType(string $actorType): bool
    {
        return in_array($actorType, $this->allowed_actor_types ?? [], true);
    }

    public function allowsMimeGroup(string $group): bool
    {
        return in_array($group, $this->allowed_mime_groups ?? [], true);
    }

    public function permissionKey(): ?string
    {
        $permission = $this->settings['permission'] ?? null;

        return is_string($permission) && $permission !== '' ? $permission : null;
    }
}
