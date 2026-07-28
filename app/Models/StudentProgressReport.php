<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentProgressReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'tentor_id',
        'user_id',
        'package_id',
        'study_group_id',
        'user_package_access_id',
        'period_start',
        'period_end',
        'progress_percent',
        'mastery_score',
        'discipline_score',
        'participation_score',
        'summary',
        'strengths',
        'improvements',
        'next_target',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'progress_percent' => 'integer',
        'mastery_score' => 'integer',
        'discipline_score' => 'integer',
        'participation_score' => 'integer',
    ];

    public function tentor(): BelongsTo
    {
        return $this->belongsTo(Tentor::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class, 'package_id', 'package_id');
    }

    public function studyGroup(): BelongsTo
    {
        return $this->belongsTo(StudyGroup::class);
    }

    public function packageAccess(): BelongsTo
    {
        return $this->belongsTo(
            UserPackageAcces::class,
            'user_package_access_id',
            'user_package_access_id'
        );
    }
}
