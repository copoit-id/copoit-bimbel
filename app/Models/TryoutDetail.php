<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TryoutDetail extends Model
{
    use HasFactory;

    protected $table = 'tryout_details';
    protected $primaryKey = 'tryout_detail_id';

    protected $fillable = [
        'tryout_id',
        'type_subtest',
        'material_category_id',
        'duration',
        'passing_score',
        'passing_type',
    ];

    protected $casts = [
        'duration' => 'integer',
        'material_category_id' => 'integer',
        'passing_score' => 'decimal:2',
        'passing_type' => 'string',
    ];

    public function tryout()
    {
        return $this->belongsTo(Tryout::class, 'tryout_id', 'tryout_id');
    }

    public function questions()
    {
        return $this->hasMany(Question::class, 'tryout_detail_id', 'tryout_detail_id');
    }

    public function materialCategory()
    {
        return $this->belongsTo(MaterialCategory::class, 'material_category_id', 'category_id');
    }
}
