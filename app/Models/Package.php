<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Package extends Model
{
    use HasFactory;

    protected $guarded = ['package_id'];
    protected $primaryKey = 'package_id';
    protected $casts = [
        'is_active' => 'boolean',
        'price' => 'decimal:0'
    ];

    // Direct relationships (dari manajemen package lama)
    public function directTryouts()
    {
        return $this->hasMany(Tryout::class, 'package_id', 'package_id');
    }

    public function directClasses()
    {
        return $this->hasMany(ClassModel::class, 'package_id', 'package_id');
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

    // Other relationships
    public function userAccess()
    {
        return $this->hasMany(UserPackageAcces::class, 'package_id', 'package_id');
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    // Accessors
    public function getFormattedPriceAttribute()
    {
        return 'Rp ' . number_format($this->price, 0, ',', '.');
    }

    public function getDurationTextAttribute()
    {
        return $this->duration . ' Hari';
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
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
}
