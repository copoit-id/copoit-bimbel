<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LandingpageGallery extends Model
{
    use HasFactory;

    protected $table = 'landingpage_gallery';

    protected $fillable = [
        'title',
        'description',
        'image',
        'category',
        'order',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean'
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('order', 'asc');
    }

    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }
}
