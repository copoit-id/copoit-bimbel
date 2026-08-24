<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TutorAttendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'class_session_id',
        'tentor_id',
        'status',
        'approval_status',
        'check_in_at',
        'check_out_at',
        'photo_path',
        'source',
        'notes',
        'marked_by',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'check_in_at' => 'datetime',
        'check_out_at' => 'datetime',
        'approved_at' => 'datetime',
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

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function payrollItems(): HasMany
    {
        return $this->hasMany(TutorPayrollItem::class);
    }
}
