<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ToolsBillPrintFolder extends Model
{
    protected $table = 'tools_bill_print_folders';

    protected $fillable = [
        'user_id',
        'folder_name',
        'folder_date',
        'images',
        'image_count',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'images' => 'array',
            'image_count' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
