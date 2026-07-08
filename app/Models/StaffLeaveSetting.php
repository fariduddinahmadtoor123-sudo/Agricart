<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StaffLeaveSetting extends Model
{
    protected $fillable = ['monthly_paid_leaves'];

    public static function current(): self
    {
        $setting = static::query()->first();

        if ($setting === null) {
            $setting = static::query()->create(['monthly_paid_leaves' => 2]);
        }

        return $setting;
    }
}
