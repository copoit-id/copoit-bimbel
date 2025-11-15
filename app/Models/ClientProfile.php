<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClientProfile extends Model
{
    protected $table = 'client_profile';

    protected $fillable = [
        'nama_bimbel',
        'logo',
        'warna_primary',
        'warna_secondary',
        'enable_certificate_management',
    ];

    protected $casts = [
        'enable_certificate_management' => 'boolean',
    ];
}
