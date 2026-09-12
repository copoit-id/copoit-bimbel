<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AttendanceSetting extends Model
{
    use HasFactory;

    public const MODE_BUTTON = 'button';

    public const MODE_PHOTO = 'photo';

    protected $fillable = [
        'class_schedule_id',
        'mode',
        'open_minutes_before',
        'close_minutes_after',
        'allow_admin_override',
    ];

    protected $casts = [
        'open_minutes_before' => 'integer',
        'close_minutes_after' => 'integer',
        'allow_admin_override' => 'boolean',
    ];

    public function requiresPhoto(): bool
    {
        return $this->mode === self::MODE_PHOTO;
    }

    public function schedule()
    {
        return $this->belongsTo(ClassSchedule::class, 'class_schedule_id');
    }
}
