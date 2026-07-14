<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TutorAttendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'class_session_id',
        'tentor_id',
        'status',
        'check_in_at',
        'check_out_at',
        'source',
        'notes',
        'marked_by',
    ];

    protected $casts = [
        'check_in_at' => 'datetime',
        'check_out_at' => 'datetime',
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(ClassSession::class, 'class_session_id');
    }

    public function tentor(): BelongsTo
    {
        return $this->belongsTo(Tentor::class);
    }

    public function marker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'marked_by');
    }
}
