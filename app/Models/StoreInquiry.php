<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StoreInquiry extends Model
{
    public const STATUS_NEW = 'new';

    public const STATUS_READ = 'read';

    public const STATUS_PENDING = 'pending';

    public const STATUS_CLOSED = 'closed';

    /** @var list<string> */
    public const STATUSES = [
        self::STATUS_NEW,
        self::STATUS_READ,
        self::STATUS_PENDING,
        self::STATUS_CLOSED,
    ];

    /** @var list<string> */
    protected $fillable = [
        'name',
        'phone',
        'email',
        'message',
        'status',
        'source_url',
        'ip_hash',
        'user_agent',
        'read_at',
        'closed_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'read_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }
}
