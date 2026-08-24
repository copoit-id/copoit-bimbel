<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Article extends Model
{
    public const STATUS_DRAFT = 'draft';
    public const STATUS_PUBLISHED = 'published';

    protected $guarded = ['id'];

    protected $casts = [
        'published_at' => 'datetime',
    ];

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query
            ->where('status', self::STATUS_PUBLISHED)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    public function getCoverUrlAttribute(): ?string
    {
        if (!$this->cover_image) {
            return null;
        }

        if (Str::startsWith($this->cover_image, ['http://', 'https://', '//'])) {
            return $this->cover_image;
        }

        $path = ltrim($this->cover_image, '/');

        if (Str::startsWith($path, 'storage/')) {
            return Storage::disk('public')->exists(Str::after($path, 'storage/'))
                ? asset($path)
                : null;
        }

        if (Storage::disk('public')->exists($path)) {
            return Storage::disk('public')->url($path);
        }

        if (file_exists(public_path($path))) {
            return asset($path);
        }

        return null;
    }

    public function getReadingMinutesAttribute(): int
    {
        $wordCount = str_word_count(strip_tags((string) $this->content));

        return max(1, (int) ceil($wordCount / 200));
    }

    public function getPublishedDateLabelAttribute(): string
    {
        $date = $this->published_at ?: $this->created_at;

        return $date instanceof Carbon ? $date->format('d M Y') : '-';
    }
}
