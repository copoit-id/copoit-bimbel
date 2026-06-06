<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MaterialCategory extends Model
{
    use HasFactory;

    protected $primaryKey = 'category_id';
    protected $guarded = ['category_id'];

    protected $casts = [
        'is_active' => 'boolean',
        'order_number' => 'integer',
    ];

    /**
     * Relasi ke materials
     */
    public function materials(): BelongsToMany
    {
        return $this->belongsToMany(
            Material::class,
            'material_category_pivot',
            'category_id',
            'material_id'
        )->withTimestamps();
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id', 'category_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id', 'category_id')->ordered();
    }

    public function activeChildren(): HasMany
    {
        return $this->children()->active();
    }

    /**
     * Scope: Active categories
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeRoot($query)
    {
        return $query->whereNull('parent_id');
    }

    /**
     * Scope: Ordered
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('order_number', 'asc');
    }

    public function getDisplayNameAttribute(): string
    {
        return $this->parent
            ? $this->parent->name . ' - ' . $this->name
            : $this->name;
    }
}
