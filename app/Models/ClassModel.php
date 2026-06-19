<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClassModel extends Model
{
    use HasFactory;

    protected $table = 'classes';
    protected $primaryKey = 'class_id';
    protected $guarded = ['class_id'];

    protected $casts = [
        'schedule_time' => 'datetime',
    ];

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

    public function assessments()
    {
        return $this->belongsToMany(Tryout::class, 'class_assessments', 'class_id', 'tryout_id')
            ->withPivot('assessment_type')
            ->withTimestamps();
    }

    public function schedules()
    {
        return $this->hasMany(ClassSchedule::class, 'class_id', 'class_id');
    }

    public function sessions()
    {
        return $this->hasMany(ClassSession::class, 'class_id', 'class_id');
    }

    public function preTest()
    {
        return $this->assessments()->wherePivot('assessment_type', 'pre_test');
    }

    public function postTest()
    {
        return $this->assessments()->wherePivot('assessment_type', 'post_test');
    }
}
