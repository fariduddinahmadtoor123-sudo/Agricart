<?php

namespace App\Services\Communication;

use App\Models\Communication\Channel;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;

class ChannelRegistry
{
    /**
     * @return Collection<int, Channel>
     */
    public function allActive(): Collection
    {
        if (! Schema::hasTable('comm_channels')) {
            return collect();
        }

        return Channel::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();
    }

    public function findByKey(string $key): ?Channel
    {
        if (! Schema::hasTable('comm_channels')) {
            return null;
        }

        return Channel::query()->where('key', $key)->first();
    }

    public function requireByKey(string $key): Channel
    {
        $channel = $this->findByKey($key);

        if ($channel === null) {
            throw new InvalidArgumentException("Unknown communication channel [{$key}].");
        }

        if (! $channel->is_active) {
            throw new InvalidArgumentException("Communication channel [{$key}] is inactive.");
        }

        return $channel;
    }

    public function isRegisteredActorType(string $actorType): bool
    {
        return array_key_exists($actorType, config('communication.actor_types', []));
    }

    public function actorModelClass(string $actorType): ?string
    {
        $map = config('communication.actor_types', []);

        if (! array_key_exists($actorType, $map)) {
            throw new InvalidArgumentException("Unknown actor type [{$actorType}]. Register it in config/communication.php.");
        }

        $class = $map[$actorType];

        return is_string($class) && $class !== '' ? $class : null;
    }

    public function isRegisteredLinkableType(string $linkableType): bool
    {
        return array_key_exists($linkableType, config('communication.linkable_types', []));
    }

    public function linkableModelClass(string $linkableType): string
    {
        $map = config('communication.linkable_types', []);

        if (! isset($map[$linkableType]) || ! is_string($map[$linkableType])) {
            throw new InvalidArgumentException("Unknown linkable type [{$linkableType}]. Register it in config/communication.php.");
        }

        return $map[$linkableType];
    }

    public function mimeGroupFor(string $mime): ?string
    {
        foreach (config('communication.mime_groups', []) as $group => $mimes) {
            if (in_array($mime, $mimes, true)) {
                return $group;
            }
        }

        return null;
    }

    /**
     * Upsert channel definitions from config (and optional extra overrides).
     * Used by seeder and extensibility tests — no hard-coded channel branches.
     *
     * @param  array<string, array<string, mixed>>  $definitions
     */
    public function syncDefinitions(array $definitions): void
    {
        foreach ($definitions as $key => $definition) {
            Channel::query()->updateOrCreate(
                ['key' => $key],
                [
                    'name' => (string) ($definition['name'] ?? $key),
                    'is_active' => (bool) ($definition['is_active'] ?? true),
                    'allowed_actor_types' => array_values($definition['allowed_actor_types'] ?? []),
                    'allows_groups' => (bool) ($definition['allows_groups'] ?? false),
                    'allows_attachments' => (bool) ($definition['allows_attachments'] ?? true),
                    'allowed_mime_groups' => array_values($definition['allowed_mime_groups'] ?? []),
                    'allows_tasks' => (bool) ($definition['allows_tasks'] ?? false),
                    'visibility' => (string) ($definition['visibility'] ?? 'participants'),
                    'settings' => $definition['settings'] ?? null,
                    'sort_order' => (int) ($definition['sort_order'] ?? 0),
                ],
            );
        }
    }
}
