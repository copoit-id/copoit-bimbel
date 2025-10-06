<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Models\Tryout;
use App\Models\UserAnswer;
use App\Models\Certificate;

class Exam extends Model
{
    protected $table = 'exams';
    protected $guarded = ['exam_id'];
    protected $primaryKey = 'exam_id';

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
    public function tryout()
    {
        return $this->belongsTo(Tryout::class, 'tryout_id');
    }
    public function userAnswers()
    {
        return $this->hasMany(UserAnswer::class, 'exam_id');
    }
    public function certificate()
    {
        return $this->hasOne(Certificate::class, 'exam_id');
    }
}
