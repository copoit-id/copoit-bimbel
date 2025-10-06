<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserAnswer extends Model
{
    use HasFactory;

    protected $table = 'user_answers';
    protected $primaryKey = 'user_answer_id';

    protected $fillable = [
        'user_id',
        'tryout_id',
        'tryout_detail_id',
        'attempt_token',
        'started_at',
        'finished_at',
        'score',
        'correct_answers',
        'total_questions',
        'status'
    ];

    protected $casts = [
        'started_at' => 'datetime:Y-m-d H:i:s',
        'finished_at' => 'datetime:Y-m-d H:i:s',
        'score' => 'decimal:2'
    ];

    // Pastikan timezone Jakarta untuk semua datetime
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($userAnswer) {
            if (!$userAnswer->started_at) {
                $userAnswer->started_at = Carbon::now('Asia/Jakarta');
            }
            if (!$userAnswer->status) {
                $userAnswer->status = 'in_progress';
            }
        });
    }

    // Accessor untuk timezone Jakarta
    public function getStartedAtAttribute($value)
    {
        return $value ? Carbon::parse($value)->setTimezone('Asia/Jakarta') : null;
    }

    public function getFinishedAtAttribute($value)
    {
        return $value ? Carbon::parse($value)->setTimezone('Asia/Jakarta') : null;
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function tryout()
    {
        return $this->belongsTo(Tryout::class, 'tryout_id', 'tryout_id');
    }

    public function tryoutDetail()
    {
        return $this->belongsTo(TryoutDetail::class, 'tryout_detail_id', 'tryout_detail_id');
    }

    public function userAnswerDetails()
    {
        return $this->hasMany(UserAnswerDetail::class, 'user_answer_id', 'user_answer_id');
    }
}
