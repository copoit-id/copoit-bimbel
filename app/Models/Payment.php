<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Models\Package;
use App\Models\Discount;
use Carbon\Carbon;
use RuntimeException;

class Payment extends Model
{
    use HasFactory;

    protected $table = 'payments';
    protected $guarded = [];
    protected $primaryKey = 'payment_id';

    protected $casts = [
        'amount' => 'decimal:0',
        'original_amount' => 'decimal:0',
        'discount_amount' => 'decimal:0',
        'unique_code' => 'integer',
        'unique_code_date' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'paid_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function package()
    {
        return $this->belongsTo(Package::class, 'package_id', 'package_id');
    }

    public function discount()
    {
        return $this->belongsTo(Discount::class);
    }

    public function affiliateCommission()
    {
        return $this->hasOne(AffiliateCommission::class, 'payment_id', 'payment_id');
    }

    public function getFormattedAmountAttribute(): string
    {
        return 'Rp ' . number_format((float) $this->total_amount, 0, ',', '.');
    }

    public static function generateManualUniqueCode(array $reservedCodes = []): int
    {
        $usedCodes = self::query()
            ->where('payment_method', 'manual')
            ->where('status', self::STATUS_PENDING)
            ->whereDate('unique_code_date', now()->toDateString())
            ->whereNotNull('unique_code')
            ->pluck('unique_code')
            ->map(fn ($code) => (int) $code)
            ->all();

        $blockedCodes = array_unique(array_merge($usedCodes, array_map('intval', $reservedCodes)));

        if (count($blockedCodes) >= 999) {
            throw new RuntimeException('Kode unik pembayaran manual hari ini sudah habis.');
        }

        for ($attempt = 0; $attempt < 20; $attempt++) {
            $code = random_int(1, 999);

            if (!in_array($code, $blockedCodes, true)) {
                return $code;
            }
        }

        for ($code = 1; $code <= 999; $code++) {
            if (!in_array($code, $blockedCodes, true)) {
                return $code;
            }
        }

        throw new RuntimeException('Kode unik pembayaran manual hari ini sudah habis.');
    }

    public static function isManualUniqueCodeAvailable(int $code): bool
    {
        return !self::query()
            ->where('payment_method', 'manual')
            ->where('status', self::STATUS_PENDING)
            ->whereDate('unique_code_date', now()->toDateString())
            ->where('unique_code', $code)
            ->exists();
    }

    // Status constants
    const STATUS_PENDING = 'pending';
    const STATUS_SUCCESS = 'success';
    const STATUS_FAILED = 'failed';
    const STATUS_EXPIRED = 'expired';

    public function isPending()
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isSuccess()
    {
        return $this->status === self::STATUS_SUCCESS;
    }

    public function isFailed()
    {
        return $this->status === self::STATUS_FAILED;
    }

    public function isExpired()
    {
        return $this->status === self::STATUS_EXPIRED;
    }
}
