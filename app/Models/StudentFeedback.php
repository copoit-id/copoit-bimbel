<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentFeedback extends Model
{
    use HasFactory;

    protected $table = 'student_feedback';

    protected $fillable = [
        'tentor_id',
        'user_id',
        'study_group_id',
        'class_session_id',
        'scope',
        'title',
        'feedback',
        'is_visible_to_student',
    ];

    protected $casts = [
        'is_visible_to_student' => 'boolean',
    ];

    public function tentor(): BelongsTo
    {
        return $this->belongsTo(Tentor::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function studyGroup(): BelongsTo
    {
        return $this->belongsTo(StudyGroup::class);
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(ClassSession::class, 'class_session_id');
    }
}
