<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class TutorPayroll extends Model
{
    use HasFactory;

    protected $fillable = [
        'tentor_id',
        'period_start',
        'period_end',
        'rate_per_attendance',
        'gross_amount',
        'adjustment_amount',
        'net_amount',
        'status',
        'notes',
        'generated_by',
        'paid_by',
        'paid_at',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'rate_per_attendance' => 'decimal:0',
        'gross_amount' => 'decimal:0',
        'adjustment_amount' => 'decimal:0',
        'net_amount' => 'decimal:0',
        'paid_at' => 'datetime',
    ];

    public function tentor(): BelongsTo
    {
        return $this->belongsTo(Tentor::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(TutorPayrollItem::class);
    }

    public function expense(): HasOne
    {
        return $this->hasOne(Expense::class);
    }
}
