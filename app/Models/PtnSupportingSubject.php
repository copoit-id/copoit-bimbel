<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PtnSupportingSubject extends Model
{
    protected $fillable = [
        'kode_prodi',
        'perguruan_tinggi',
        'nama_prodi',
        'jenjang',
        'mapel_pendukung',
        'imported_at',
    ];

    protected $casts = [
        'mapel_pendukung' => 'array',
        'imported_at' => 'datetime',
    ];
}
