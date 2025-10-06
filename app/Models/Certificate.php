<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Carbon\Carbon;

class Certificate extends Model
{
    use HasFactory;

    protected $primaryKey = 'certificate_id';

    protected $guarded = ['certificate_id'];

    protected $casts = [
        'metadata' => 'array',
        'issued_date' => 'date',
        'expired_date' => 'date'
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($certificate) {
            if (!$certificate->certificate_number) {
                $certificate->certificate_number = static::generateCertificateNumber();
            }
            if (!$certificate->verification_code) {
                $certificate->verification_code = static::generateVerificationCode();
            }
        });
    }

    public function issuedBy()
    {
        return $this->belongsTo(User::class, 'issued_by');
    }

    public function isExpired()
    {
        return $this->expired_date && Carbon::now()->gt($this->expired_date);
    }

    public function isActive()
    {
        return $this->status === 'active' && !$this->isExpired();
    }

    public static function generateCertificateNumber()
    {
        do {
            $number = 'CERT-' . date('Y') . '-' . str_pad(rand(1, 99999), 5, '0', STR_PAD_LEFT);
        } while (static::where('certificate_number', $number)->exists());

        return $number;
    }

    public static function generateVerificationCode()
    {
        do {
            $code = Str::random(32);
        } while (static::where('verification_code', $code)->exists());

        return $code;
    }

    public function getStatusBadgeAttribute()
    {
        $badges = [
            'active' => 'bg-green-100 text-green-700',
            'revoked' => 'bg-red-100 text-red-700',
            'expired' => 'bg-gray-100 text-gray-700'
        ];

        return $badges[$this->status] ?? 'bg-gray-100 text-gray-700';
    }

    public function getStatusTextAttribute()
    {
        $texts = [
            'active' => 'Aktif',
            'revoked' => 'Dicabut',
            'expired' => 'Kedaluwarsa'
        ];

        return $texts[$this->status] ?? 'Tidak Diketahui';
    }
}