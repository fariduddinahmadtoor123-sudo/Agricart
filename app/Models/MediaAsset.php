<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class MediaAsset extends Model
{
    protected $fillable = [
        'uuid',
        'disk',
        'path',
        'visibility',
        'original_name',
        'display_name',
        'mime_type',
        'extension',
        'byte_size',
        'width',
        'height',
        'checksum',
        'source_module',
        'optimization_status',
        'metadata',
        'created_by',
        'trashed_at',
    ];

    protected function casts(): array
    {
        return [
            'byte_size' => 'integer',
            'width' => 'integer',
            'height' => 'integer',
            'metadata' => 'array',
            'trashed_at' => 'datetime',
        ];
    }

    public function usages(): HasMany
    {
        return $this->hasMany(MediaUsage::class);
    }

    public function url(): ?string
    {
        if (blank($this->path)) {
            return null;
        }

        if (
            $this->disk === 'local'
            && str_starts_with($this->path, 'users/cnic/')
            && filled($this->metadata['user_id'] ?? null)
            && in_array($this->metadata['side'] ?? null, ['front', 'back'], true)
        ) {
            return route('admin.users.documents.show', [
                'user' => (int) $this->metadata['user_id'],
                'side' => $this->metadata['side'],
            ], absolute: false);
        }

        if ($this->visibility !== 'public') {
            return null;
        }

        return Storage::disk($this->disk)->url($this->path);
    }

    public function isImage(): bool
    {
        return str_starts_with((string) $this->mime_type, 'image/');
    }
}
