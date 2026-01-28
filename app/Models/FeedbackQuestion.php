<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FeedbackQuestion extends Model
{
    use HasFactory;

    protected $table = 'feedback_questions';
    protected $primaryKey = 'feedback_question_id';
    protected $guarded = ['feedback_question_id'];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function tryout()
    {
        return $this->belongsTo(Tryout::class, 'tryout_id', 'tryout_id');
    }

    public function answers()
    {
        return $this->hasMany(FeedbackAnswer::class, 'feedback_question_id', 'feedback_question_id');
    }
}
