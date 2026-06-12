<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Material extends Model
{
    use HasFactory;

    protected $primaryKey = 'material_id';
    protected $guarded = ['material_id'];

    protected $casts = [
        'is_active' => 'boolean',
        'is_for_sale' => 'boolean',
        'is_displayed' => 'boolean',
        'metadata' => 'array',
        'duration_minutes' => 'integer',
        'order_number' => 'integer',
        'price' => 'decimal:0',
        'access_duration_value' => 'integer',
    ];

    /**
     * Relasi ke kategori (many-to-many)
     */
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(
            MaterialCategory::class,
            'material_category_pivot',
            'material_id',
            'category_id'
        )->withTimestamps();
    }

    /**
     * Relasi ke user access
     */
    public function userAccess(): HasMany
    {
        return $this->hasMany(UserMaterialAccess::class, 'material_id');
    }

    /**
     * Relasi ke progress logs
     */
    public function progressLogs(): HasMany
    {
        return $this->hasMany(MaterialProgressLog::class, 'material_id');
    }

    /**
     * Relasi ke packages (many-to-many dengan pivot data)
     */
    public function packages(): BelongsToMany
    {
        return $this->belongsToMany(Package::class, 'package_materials', 'material_id', 'package_id')
            ->withPivot(['section_name', 'order_number', 'is_required', 'unlock_condition'])
            ->orderBy('package_materials.order_number');
    }

    /**
     * Polymorphic relationship untuk detail_packages
     */
    public function detailPackages(): MorphMany
    {
        return $this->morphMany(DetailPackage::class, 'detailable');
    }

    /**
     * Relasi ke creator
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Scope: Active materials
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: By type
     */
    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope: By category
     */
    public function scopeByCategory($query, $categoryId)
    {
        $categoryIds = [(int) $categoryId];
        $category = MaterialCategory::query()
            ->with('activeChildren')
            ->find($categoryId);

        if ($category) {
            $categoryIds = $category->activeChildren
                ->pluck('category_id')
                ->prepend($category->category_id)
                ->map(fn($id) => (int) $id)
                ->unique()
                ->values()
                ->all();
        }

        return $query->whereHas('categories', function ($q) use ($categoryIds) {
            $q->whereIn('material_categories.category_id', $categoryIds);
        });
    }

    /**
     * Scope: Order by order_number
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('order_number', 'asc');
    }

    /**
     * Check if user has access to this material
     */
    public function userHasAccess(int $userId): bool
    {
        return $this->userAccess()
            ->where('user_id', $userId)
            ->where('status', '!=', 'not_started')
            ->exists();
    }

    /**
     * Get user access record
     */
    public function getUserAccess(int $userId): ?UserMaterialAccess
    {
        return $this->userAccess()
            ->where('user_id', $userId)
            ->first();
    }

    /**
     * Get formatted duration attribute
     */
    public function getFormattedDurationAttribute(): ?string
    {
        if (!$this->duration_minutes) {
            return null;
        }

        $hours = floor($this->duration_minutes / 60);
        $minutes = $this->duration_minutes % 60;

        if ($hours > 0) {
            return $minutes > 0
                ? "{$hours}j {$minutes}m"
                : "{$hours}j";
        }

        return "{$minutes}m";
    }

    /**
     * Get type label attribute
     */
    public function getTypeLabelAttribute(): string
    {
        return match ($this->type) {
            'video' => 'Video',
            'document' => 'Dokumen',
            'live_session' => 'Live Session',
            default => 'Materi',
        };
    }

    /**
     * Get type icon attribute
     */
    public function getTypeIconAttribute(): string
    {
        return match ($this->type) {
            'video' => 'ri-video-line',
            'document' => 'ri-file-text-line',
            'live_session' => 'ri-live-line',
            default => 'ri-book-line',
        };
    }

    /**
     * Check if user can access this material (via any method)
     */
    public function canUserAccess(int $userId): bool
    {
        // Check via package. Package assignment uses detail_packages.
        $hasPackageAccess = \DB::table('detail_packages')
            ->join('user_package_access', 'detail_packages.package_id', '=', 'user_package_access.package_id')
            ->where('detail_packages.detailable_type', self::class)
            ->where('detail_packages.detailable_id', $this->material_id)
            ->where('user_package_access.user_id', $userId)
            ->where('user_package_access.status', 'active')
            ->where(function ($query) {
                $query->whereNull('user_package_access.end_date')
                    ->orWhere('user_package_access.end_date', '>', now());
            })
            ->exists();

        if ($hasPackageAccess) {
            return true;
        }

        // Check via direct user access
        $hasDirectAccess = $this->userAccess()
            ->where('user_id', $userId)
            ->where('status', '!=', 'not_started')
            ->where(function ($q) {
                $q->whereNull('expires_at')
                  ->orWhere('expires_at', '>', now());
            })
            ->exists();

        if ($hasDirectAccess) {
            return true;
        }

        // Check via individual purchase (if material has price > 0)
        if ($this->price > 0) {
            $hasIndividualPurchase = \App\Models\IndividualPurchase::where('user_id', $userId)
                ->where('purchasable_type', self::class)
                ->where('purchasable_id', $this->material_id)
                ->where('status', 'approved')
                ->where(function ($query) {
                    $query->whereNull('access_expires_at')
                        ->orWhere('access_expires_at', '>', now());
                })
                ->exists();

            if ($hasIndividualPurchase) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if user has purchased this individually
     */
    public function hasUserPurchased(int $userId): bool
    {
        return \App\Models\IndividualPurchase::where('user_id', $userId)
            ->where('purchasable_type', self::class)
            ->where('purchasable_id', $this->material_id)
            ->where('status', 'approved')
            ->where(function ($query) {
                $query->whereNull('access_expires_at')
                    ->orWhere('access_expires_at', '>', now());
            })
            ->exists();
    }

    /**
     * Check if user has pending purchase for this
     */
    public function hasPendingPurchase(int $userId): bool
    {
        return \App\Models\IndividualPurchase::where('user_id', $userId)
            ->where('purchasable_type', self::class)
            ->where('purchasable_id', $this->material_id)
            ->where('status', 'pending')
            ->exists();
    }
}
