<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DetailPackage extends Model
{
    use HasFactory;

    protected $table = 'detail_packages';
    protected $primaryKey = 'detail_package_id';
    protected $guarded = ['detail_package_id'];

    public function package()
    {
        return $this->belongsTo(Package::class, 'package_id', 'package_id');
    }

    public function detailable()
    {
        return $this->morphTo();
    }
}