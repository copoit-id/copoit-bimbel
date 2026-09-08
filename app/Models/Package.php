<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Package extends Model
{
    use HasFactory;

    public const ENROLLMENT_DIRECT_PURCHASE = 'direct_purchase';

    public const ENROLLMENT_PROGRAM = 'program';

    protected $guarded = ['package_id'];

    protected $primaryKey = 'package_id';

    protected $casts = [
        'is_displayed' => 'boolean',
        'price' => 'decimal:0',
        'access_duration_value' => 'integer',
    ];

    public function freeClaimTryout(): BelongsTo
    {
        return $this->belongsTo(Tryout::class, 'free_claim_tryout_id', 'tryout_id');
    }

    // Detail package relationships (sistem baru dengan checklist)
    public function detailPackages()
    {
        return $this->hasMany(DetailPackage::class, 'package_id', 'package_id');
    }

    // Many-to-many relationships through detail_packages
    public function tryouts()
    {
        return $this->hasManyThrough(
            Tryout::class,
            DetailPackage::class,
            'package_id',
            'tryout_id',
            'package_id',
            'detailable_id'
        )->where('detail_packages.detailable_type', Tryout::class);
    }

    public function classes()
    {
        return $this->hasManyThrough(
            ClassModel::class,
            DetailPackage::class,
            'package_id',
            'class_id',
            'package_id',
            'detailable_id'
        )->where('detail_packages.detailable_type', ClassModel::class);
    }

    public function schedules(): BelongsToMany
    {
        $schedule = new ClassSchedule;

        return $this->belongsToMany(
            ClassSchedule::class,
            'detail_packages',
            'package_id',
            'detailable_id',
            'package_id',
            'id'
        )->wherePivot('detailable_type', $schedule->getMorphClass());
    }

    public function bookingRule(): HasOne
    {
        return $this->hasOne(PackageBookingRule::class, 'package_id', 'package_id');
    }

    public function bookingRequests(): HasMany
    {
        return $this->hasMany(ScheduleBookingRequest::class, 'package_id', 'package_id');
    }

    public function studyGroups(): HasMany
    {
        return $this->hasMany(StudyGroup::class, 'package_id', 'package_id');
    }

    // Other relationships
    public function userAccess()
    {
        return $this->hasMany(UserPackageAcces::class, 'package_id', 'package_id');
    }

    public function tesKorans()
    {
        return $this->hasManyThrough(
            TesKoran::class,
            DetailPackage::class,
            'package_id',
            'id',
            'package_id',
            'detailable_id'
        )->where('detail_packages.detailable_type', TesKoran::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    // Accessors
    public function getFormattedPriceAttribute()
    {
        return 'Rp '.number_format($this->price, 0, ',', '.');
    }

    public function getDurationTextAttribute()
    {
        if (($this->access_duration_unit ?? 'forever') === 'forever') {
            return 'Selamanya';
        }

        $unitLabel = match ($this->access_duration_unit) {
            'day' => 'Hari',
            'week' => 'Minggu',
            'month' => 'Bulan',
            'year' => 'Tahun',
            default => 'Hari',
        };

        return ((int) $this->access_duration_value).' '.$unitLabel;
    }

    // Scopes
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    // Stats methods
    public function getTotalUsersAttribute()
    {
        return $this->userAccess()->count();
    }

    public function getActiveUsersAttribute()
    {
        return $this->userAccess()->where('status', 'active')->count();
    }

    public function getExpiredUsersAttribute()
    {
        return $this->userAccess()->where('status', 'expired')->count();
    }

    public function getTotalTryoutsAttribute()
    {
        return $this->tryouts()->count();
    }

    public function getTotalClassesAttribute()
    {
        return $this->classes()->count();
    }

    public function getTotalRevenueAttribute()
    {
        return $this->payments()->where('status', 'success')->sum('total_amount');
    }

    // Materials relationships
    public function materials()
    {
        return $this->belongsToMany(Material::class, 'package_materials', 'package_id', 'material_id')
            ->withPivot(['section_name', 'order_number', 'is_required', 'unlock_condition'])
            ->orderBy('package_materials.order_number');
    }

    public function packageMaterials()
    {
        return $this->hasMany(PackageMaterial::class, 'package_id', 'package_id');
    }

    // Polymorphic materials through detail_packages
    public function materialsThroughDetail()
    {
        return $this->hasManyThrough(
            Material::class,
            DetailPackage::class,
            'package_id',
            'material_id',
            'package_id',
            'detailable_id'
        )->where('detail_packages.detailable_type', Material::class);
    }

    public function getTotalMaterialsAttribute()
    {
        return $this->materials()->count();
    }
}
