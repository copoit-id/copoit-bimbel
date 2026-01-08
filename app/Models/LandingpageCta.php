<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LandingpageCta extends Model
{
    protected $table = 'landingpage_cta';

    protected $fillable = [
        'title',
        'description',
        'primary_button_text',
        'secondary_button_text',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean'
    ];

    // Scope untuk mendapatkan CTA yang aktif
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
