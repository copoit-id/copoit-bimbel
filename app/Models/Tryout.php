<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tryout extends Model
{
    use HasFactory;

    protected $table = 'tryouts';
    protected $primaryKey = 'tryout_id';
    protected $guarded = ['tryout_id'];

    protected $casts = [
        'is_certification' => 'boolean',
        'is_active' => 'boolean',
        'start_date' => 'datetime',
        'end_date' => 'datetime',
    ];

    // Direct relationship (untuk tryout yang dibuat langsung di package)
    public function directPackage()
    {
        return $this->belongsTo(Package::class, 'package_id', 'package_id');
    }

    public function tryoutDetails()
    {
        return $this->hasMany(TryoutDetail::class, 'tryout_id', 'tryout_id');
    }

    // Polymorphic relationship untuk detail packages
    public function detailPackages()
    {
        return $this->morphMany(DetailPackage::class, 'detailable');
    }

    // Many-to-many relationship dengan packages melalui detail_packages
    public function packages()
    {
        return $this->morphToMany(Package::class, 'detailable', 'detail_packages', 'detailable_id', 'package_id');
    }

    // Add missing userAnswers relationship
    public function userAnswers()
    {
        return $this->hasMany(UserAnswer::class, 'tryout_id', 'tryout_id');
    }

    // Helper method untuk mendapatkan total soal
    public function getTotalQuestionsAttribute()
    {
        return $this->tryoutDetails()->withCount('questions')->get()->sum('questions_count');
    }

    // Helper method untuk mendapatkan total durasi
    public function getTotalDurationAttribute()
    {
        return $this->tryoutDetails()->sum('duration');
    }
}
