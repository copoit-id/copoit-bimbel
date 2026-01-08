<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LandingpageHero extends Model
{
    use HasFactory;

    protected $table = 'landingpage_hero';

    protected $fillable = [
        'title',
        'subtitle',
        'description',
        'button_text',
        'button_link',
        'image',
        'stat_1_number',
        'stat_1_text',
        'stat_2_number',
        'stat_2_text',
        'stat_3_number',
        'stat_3_text',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean'
    ];
}
