<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudyGroupMember extends Model
{
    use HasFactory;

    public const STATUS_AWAITING_APPROVAL = 'awaiting_approval';

    public const STATUS_AWAITING_PAYMENT = 'awaiting_payment';

    public const STATUS_PAID = 'paid';

    public const STATUS_CANCELLED = 'cancelled';

    protected $table = 'study_group_user';

    protected $fillable = [
        'study_group_id',
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

    public function studyGroup(): BelongsTo
    {
        return $this->belongsTo(StudyGroup::class);
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
