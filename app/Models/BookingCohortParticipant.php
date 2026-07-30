<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookingCohortParticipant extends Model
{
    use HasFactory;

    public const STATUS_AWAITING_PAYMENT = 'awaiting_payment';

    public const STATUS_PAID = 'paid';

    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'booking_cohort_id',
        'user_id',
        'bill_invoice_id',
        'user_package_access_id',
        'role',
        'status',
        'unit_price_snapshot',
        'paid_at',
    ];

    protected $casts = [
        'unit_price_snapshot' => 'integer',
        'paid_at' => 'datetime',
    ];

    public function cohort(): BelongsTo
    {
        return $this->belongsTo(BookingCohort::class, 'booking_cohort_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(BillInvoice::class, 'bill_invoice_id');
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
