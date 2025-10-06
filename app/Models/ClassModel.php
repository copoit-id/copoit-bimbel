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

    // Direct relationship (untuk kelas yang dibuat langsung di package)
    public function directPackage()
    {
        return $this->belongsTo(Package::class, 'package_id', 'package_id');
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
}
