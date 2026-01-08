<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LandingpageTestimonial extends Model
{
    use HasFactory;

    protected $table = 'landingpage_testimonials';

    protected $fillable = [
        'name',
        'position',
        'content',
        'photo',
        'rating',
        'order',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'rating' => 'integer'
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('order', 'asc');
    }

    public function getStarsAttribute()
    {
        return str_repeat('★', $this->rating) . str_repeat('☆', 5 - $this->rating);
    }
}
