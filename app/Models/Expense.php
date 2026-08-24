<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Expense extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'amount',
        'spent_at',
        'notes',
        'created_by',
        'tutor_payroll_id',
    ];

    protected $casts = [
        'spent_at' => 'datetime',
        'amount' => 'decimal:0',
    ];

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function tutorPayroll(): BelongsTo
    {
        return $this->belongsTo(TutorPayroll::class);
    }
}
