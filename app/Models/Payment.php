<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use RuntimeException;

class Payment extends Model
{
    use HasFactory;

    protected $table = 'payments';

    protected $guarded = [];

    protected $primaryKey = 'payment_id';

    protected $casts = [
        'amount' => 'decimal:0',
        'total_amount' => 'decimal:0',
        'original_amount' => 'decimal:0',
        'discount_amount' => 'decimal:0',
        'unique_code' => 'integer',
        'unique_code_date' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'paid_at' => 'datetime',
        'confirmed_at' => 'datetime',
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

    public function installments(): HasMany
    {
        return $this->hasMany(PaymentInstallment::class, 'payment_id', 'payment_id')
            ->orderByDesc('paid_at');
    }

    public function getPaidAmountAttribute(): int
    {
        $installmentCount = $this->attributes['installments_count'] ?? null;

        if ($installmentCount !== null) {
            return (int) $installmentCount > 0
                ? (int) ($this->attributes['installments_paid_amount'] ?? 0)
                : ($this->status === self::STATUS_SUCCESS ? (int) $this->total_amount : 0);
        }

        if ($this->relationLoaded('installments')) {
            $paidAmount = (int) $this->installments->sum('amount');

            return $paidAmount > 0 || $this->status !== self::STATUS_SUCCESS
                ? $paidAmount
                : (int) $this->total_amount;
        }

        $paidAmount = (int) $this->installments()->sum('amount');

        return $paidAmount > 0 || $this->status !== self::STATUS_SUCCESS
            ? $paidAmount
            : (int) $this->total_amount;
    }

    public function getRemainingAmountAttribute(): int
    {
        return max(0, (int) $this->total_amount - $this->paid_amount);
    }

    public function isManualEntry(): bool
    {
        return (bool) ($this->paymentDetailsArray()['manual'] ?? false);
    }

    public function getFormattedAmountAttribute(): string
    {
        return 'Rp '.number_format((float) $this->total_amount, 0, ',', '.');
    }

    public static function generateManualUniqueCode(array $reservedCodes = []): int
    {
        $usedCodes = self::query()
            ->where('status', self::STATUS_PENDING)
            ->whereDate('unique_code_date', now()->toDateString())
            ->whereNotNull('unique_code')
            ->pluck('unique_code')
            ->map(fn ($code) => (int) $code)
            ->all();

        $blockedCodes = array_unique(array_merge($usedCodes, array_map('intval', $reservedCodes)));

        if (count($blockedCodes) >= 999) {
            throw new RuntimeException('Kode unik pembayaran hari ini sudah habis.');
        }

        for ($attempt = 0; $attempt < 20; $attempt++) {
            $code = random_int(1, 999);

            if (! in_array($code, $blockedCodes, true)) {
                return $code;
            }
        }

        for ($code = 1; $code <= 999; $code++) {
            if (! in_array($code, $blockedCodes, true)) {
                return $code;
            }
        }

        throw new RuntimeException('Kode unik pembayaran hari ini sudah habis.');
    }

    public static function isManualUniqueCodeAvailable(int $code): bool
    {
        return ! self::query()
            ->where('status', self::STATUS_PENDING)
            ->whereDate('unique_code_date', now()->toDateString())
            ->where('unique_code', $code)
            ->exists();
    }

    public function paymentDetailsArray(): array
    {
        if (is_array($this->payment_details)) {
            return $this->payment_details;
        }

        return $this->payment_details
            ? (json_decode($this->payment_details, true) ?: [])
            : [];
    }

    public function hasGatewayConfirmation(): bool
    {
        $details = $this->paymentDetailsArray();

        if ($this->payment_method === 'ipaymu') {
            $webhook = $details['ipaymu_webhook'] ?? null;
            $check = $details['ipaymu_check'] ?? null;

            return $this->hasPaidStatus($webhook) || $this->hasPaidStatus($check);
        }

        if ($this->payment_method === 'interactive_qris') {
            return ! empty($details['qris_paid_status']);
        }

        if ($this->payment_method === 'manual') {
            return (bool) $this->confirmed_at || (bool) $this->confirmed_by;
        }

        return false;
    }

    public function gatewayConfirmationStatus(): string
    {
        if ($this->status === self::STATUS_PENDING) {
            return 'waiting';
        }

        if (in_array($this->status, [self::STATUS_FAILED, self::STATUS_EXPIRED], true)) {
            return 'failed';
        }

        return $this->hasGatewayConfirmation() ? 'confirmed' : 'unverified';
    }

    public function gatewayConfirmationLabel(): string
    {
        return match ($this->gatewayConfirmationStatus()) {
            'confirmed' => $this->payment_method === 'manual' ? 'Dikonfirmasi Admin' : 'Terverifikasi Gateway',
            'waiting' => 'Menunggu Gateway',
            'failed' => 'Gagal/Expired',
            default => 'Belum Ada Bukti Gateway',
        };
    }

    public function gatewayConfirmationClass(): string
    {
        return match ($this->gatewayConfirmationStatus()) {
            'confirmed' => 'bg-green-100 text-green-700',
            'waiting' => 'bg-yellow-100 text-yellow-700',
            'failed' => 'bg-red-100 text-red-700',
            default => 'bg-orange-100 text-orange-700',
        };
    }

    public function gatewayReference(): ?string
    {
        $details = $this->paymentDetailsArray();

        return $details['ipaymu_transaction_id']
            ?? $details['qris_invoiceid']
            ?? $details['invoice_id']
            ?? $details['external_id']
            ?? $this->transaction_id;
    }

    private function hasPaidStatus(mixed $payload): bool
    {
        if (! is_array($payload)) {
            return false;
        }

        $statuses = [];
        $statusKeys = ['status', 'status_code', 'transaction_status', 'payment_status', 'paymentstatus'];

        array_walk_recursive($payload, function ($value, $key) use (&$statuses, $statusKeys) {
            if (in_array(strtolower((string) $key), $statusKeys, true)) {
                $statuses[] = strtolower((string) $value);
            }
        });

        return collect($statuses)
            ->contains(fn (string $status) => in_array($status, ['berhasil', 'success', 'paid', 'settlement', 'complete', 'completed', '1'], true));
    }

    // Status constants
    const STATUS_PENDING = 'pending';

    const STATUS_PARTIAL = 'partial';

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

    public function isPartial(): bool
    {
        return $this->status === self::STATUS_PARTIAL;
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
