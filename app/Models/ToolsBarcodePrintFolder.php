<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ToolsBarcodePrintFolder extends Model
{
    protected $table = 'tools_barcode_print_folders';

    protected $fillable = [
        'user_id',
        'sticker_name',
        'stickers',
        'sticker_count',
        'print_count',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'stickers' => 'array',
            'sticker_count' => 'integer',
            'print_count' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
