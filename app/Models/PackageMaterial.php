<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PackageMaterial extends Model
{
    use HasFactory;

    protected $table = 'package_materials';
    protected $primaryKey = 'package_material_id';
    protected $guarded = ['package_material_id'];

    protected $casts = [
        'is_required' => 'boolean',
        'unlock_condition' => 'array',
        'order_number' => 'integer',
    ];

    /**
     * Relasi ke package
     */
    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class, 'package_id');
    }

    /**
     * Relasi ke material
     */
    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class, 'material_id');
    }

    /**
     * Scope: By package
     */
    public function scopeByPackage($query, int $packageId)
    {
        return $query->where('package_id', $packageId);
    }

    /**
     * Scope: By material
     */
    public function scopeByMaterial($query, int $materialId)
    {
        return $query->where('material_id', $materialId);
    }

    /**
     * Scope: Required only
     */
    public function scopeRequired($query)
    {
        return $query->where('is_required', true);
    }

    /**
     * Scope: Optional only
     */
    public function scopeOptional($query)
    {
        return $query->where('is_required', false);
    }

    /**
     * Scope: Ordered
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('order_number', 'asc');
    }

    /**
     * Check if material has unlock condition
     */
    public function hasUnlockCondition(): bool
    {
        return !empty($this->unlock_condition);
    }

    /**
     * Get unlock condition type
     */
    public function getUnlockConditionType(): ?string
    {
        return $this->unlock_condition['type'] ?? null;
    }
}
