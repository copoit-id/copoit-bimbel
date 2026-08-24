<?php

namespace App\Models;

use App\Models\Concerns\HasIndividualPricing;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class ClassModel extends Model
{
    use HasFactory;
    use HasIndividualPricing;

    protected $table = 'classes';
    protected $primaryKey = 'class_id';
    protected $guarded = ['class_id'];

    protected $casts = [
        'schedule_time' => 'datetime',
        'is_for_sale' => 'boolean',
        'is_displayed' => 'boolean',
        'price' => 'decimal:0',
        'access_duration_value' => 'integer',
    ];

    public function tentor(): BelongsTo
    {
        return $this->belongsTo(Tentor::class, 'tentor_id');
    }

    // Polymorphic relationship untuk detail packages
    public function detailPackages()
    {
        return $this->morphMany(DetailPackage::class, 'detailable');
    }

    public function individualPurchases(): MorphMany
    {
        return $this->morphMany(IndividualPurchase::class, 'purchasable');
    }

    public function userAccess(): HasMany
    {
        return $this->hasMany(UserClassAccess::class, 'class_id', 'class_id');
    }

    // Many-to-many relationship dengan packages melalui detail_packages
    public function packages()
    {
        return $this->morphToMany(Package::class, 'detailable', 'detail_packages', 'detailable_id', 'package_id');
    }

    public function assessments()
    {
        return $this->belongsToMany(Tryout::class, 'class_assessments', 'class_id', 'tryout_id')
            ->withPivot('assessment_type')
            ->withTimestamps();
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(ClassSchedule::class, 'class_id', 'class_id');
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(ClassSession::class, 'class_id', 'class_id');
    }

    public function chatConversations(): HasMany
    {
        return $this->hasMany(ChatConversation::class, 'class_id', 'class_id');
    }

    public function preTest()
    {
        return $this->assessments()->wherePivot('assessment_type', 'pre_test');
    }

    public function postTest()
    {
        return $this->assessments()->wherePivot('assessment_type', 'post_test');
    }

    public function canUserAccess(int $userId): bool
    {
        $hasPackageAccess = DetailPackage::where('detailable_type', self::class)
            ->where('detailable_id', $this->class_id)
            ->join('user_package_access', 'detail_packages.package_id', '=', 'user_package_access.package_id')
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

        return $this->userAccess()
            ->where('user_id', $userId)
            ->active()
            ->exists();
    }
}
