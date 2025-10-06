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
        'duration',
        'passing_score'
    ];

    protected $casts = [
        'duration' => 'integer',
        'passing_score' => 'decimal:2',
    ];

    public function tryout()
    {
        return $this->belongsTo(Tryout::class, 'tryout_id', 'tryout_id');
    }

    public function questions()
    {
        return $this->hasMany(Question::class, 'tryout_detail_id', 'tryout_detail_id');
    }
}
