<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

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

    /**
     * Scope: Active categories
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: Ordered
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('order_number', 'asc');
    }
}
