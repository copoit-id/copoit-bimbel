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
        'is_toefl' => 'boolean',
        'is_irt' => 'boolean',
        'is_active' => 'boolean',
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'results_release_at' => 'datetime',
        'results_released_at' => 'datetime',
        'results_reset_at' => 'datetime',
        'assessment_type' => 'string',
    ];

    public function requiresIrtScoring(): bool
    {
        return $this->type_tryout === 'utbk_full' && $this->is_irt;
    }

    public function hasReleasedUtbk(): bool
    {
        if (! $this->results_released_at) {
            return false;
        }

        if (! $this->results_reset_at) {
            return true;
        }

        return $this->results_reset_at->lt($this->results_released_at);
    }

    public function canReleaseUtbk(): bool
    {
        return $this->requiresIrtScoring() && ! $this->hasReleasedUtbk();
    }

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

    public function classes()
    {
        return $this->belongsToMany(ClassModel::class, 'class_assessments', 'tryout_id', 'class_id')
            ->withPivot('assessment_type')
            ->withTimestamps();
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
