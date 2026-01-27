<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClientProfile extends Model
{
    protected $table = 'client_profile';

    protected $fillable = [
        'nama_bimbel',
        'logo',
        'favicon',
        'warna_primary',
        'warna_secondary',
        'enable_certificate_management',
        'header_primary_color',
        'sidebar_primary_color',
        'enable_utbk_types',
        'allow_video_thumbnail',
        'allow_register',
        'payment_mode',
        'payment_bank_name',
        'payment_account_number',
        'payment_account_holder',
        'payment_bank_note',
    ];

    protected $casts = [
        'enable_certificate_management' => 'boolean',
        'header_primary_color' => 'boolean',
        'sidebar_primary_color' => 'boolean',
        'enable_utbk_types' => 'boolean',
        'allow_video_thumbnail' => 'boolean',
        'allow_register' => 'boolean',
    ];
}
