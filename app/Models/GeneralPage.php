<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GeneralPage extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'content' => 'array',
        'settings' => 'array',
        'seo' => 'array',
        'is_active' => 'boolean',
    ];

    public static function findActiveByKey(string $pageKey): ?self
    {
        return static::query()
            ->where('page_key', $pageKey)
            ->where('is_active', true)
            ->first();
    }
}
