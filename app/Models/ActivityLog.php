<?php

namespace App\Models;

use App\Enums\ActivityEvent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ActivityLog extends Model
{
    public $timestamps = false;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'event',
        'subject_type',
        'subject_id',
        'description',
        'subject_label',
        'properties',
        'ip_address',
        'user_agent',
        'created_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'event' => ActivityEvent::class,
            'properties' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    public function actorLabel(): string
    {
        if ($this->user !== null) {
            $name = trim((string) ($this->user->name ?? ''));
            $email = trim((string) ($this->user->email ?? ''));

            // Prefer the person's name; include email so shared roles stay distinguishable.
            if ($name !== '' && $email !== '') {
                return $name.' ('.$email.')';
            }

            if ($name !== '') {
                return $name;
            }

            if ($email !== '') {
                return $email;
            }

            return '#'.$this->user->id;
        }

        $email = data_get($this->properties, 'email');

        return filled($email) ? (string) $email : __('agricart.activity.unknown_actor');
    }

    public function subjectTypeLabel(): string
    {
        if ($this->subject_type === null) {
            return '—';
        }

        $key = 'agricart.activity.subjects.'.class_basename($this->subject_type);
        $label = __($key);

        return $label === $key ? class_basename($this->subject_type) : $label;
    }
}
