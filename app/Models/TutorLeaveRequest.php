<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TutorLeaveRequest extends Model
{
    protected $fillable = ['tentor_id', 'start_at', 'end_at', 'reason', 'status', 'admin_notes', 'reviewed_by', 'reviewed_at'];
    protected $casts = ['start_at' => 'datetime', 'end_at' => 'datetime', 'reviewed_at' => 'datetime'];
    public function tentor(): BelongsTo { return $this->belongsTo(Tentor::class); }
    public function reviewer(): BelongsTo { return $this->belongsTo(User::class, 'reviewed_by'); }
}
