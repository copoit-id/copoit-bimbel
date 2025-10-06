<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class UserPackageAcces extends Model
{
    use HasFactory;

    protected $table = 'user_package_access';
    protected $primaryKey = 'user_package_access_id';
    protected $guarded = ['user_package_access_id'];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'payment_amount' => 'decimal:0',
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function package()
    {
        return $this->belongsTo(Package::class, 'package_id', 'package_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Accessors
    public function getIsActiveAttribute()
    {
        return $this->status === 'active' && $this->end_date->isFuture();
    }

    public function getIsExpiredAttribute()
    {
        return $this->end_date->isPast();
    }

    public function getDaysRemainingAttribute()
    {
        if ($this->is_expired) {
            return 0;
        }
        return Carbon::now()->diffInDays($this->end_date);
    }

    public function getStatusBadgeAttribute()
    {
        switch ($this->status) {
            case 'active':
                if ($this->is_expired) {
                    return '<span class="px-2 py-1 bg-red-100 text-red-700 rounded-full text-xs">Expired</span>';
                } elseif ($this->days_remaining <= 7) {
                    return '<span class="px-2 py-1 bg-yellow-100 text-yellow-700 rounded-full text-xs">Akan Expired</span>';
                } else {
                    return '<span class="px-2 py-1 bg-green-100 text-green-700 rounded-full text-xs">Aktif</span>';
                }
            case 'suspended':
                return '<span class="px-2 py-1 bg-red-100 text-red-700 rounded-full text-xs">Suspended</span>';
            case 'expired':
                return '<span class="px-2 py-1 bg-gray-100 text-gray-700 rounded-full text-xs">Expired</span>';
            default:
                return '<span class="px-2 py-1 bg-gray-100 text-gray-700 rounded-full text-xs">Unknown</span>';
        }
    }

    public function getFormattedPaymentAmountAttribute()
    {
        return 'Rp ' . number_format($this->payment_amount, 0, ',', '.');
    }

    public function getPaymentStatusBadgeAttribute()
    {
        switch ($this->payment_status) {
            case 'paid':
                return '<span class="px-2 py-1 bg-green-100 text-green-700 rounded-full text-xs">Lunas</span>';
            case 'pending':
                return '<span class="px-2 py-1 bg-yellow-100 text-yellow-700 rounded-full text-xs">Pending</span>';
            case 'failed':
                return '<span class="px-2 py-1 bg-red-100 text-red-700 rounded-full text-xs">Gagal</span>';
            case 'free':
                return '<span class="px-2 py-1 bg-blue-100 text-blue-700 rounded-full text-xs">Gratis</span>';
            default:
                return '<span class="px-2 py-1 bg-gray-100 text-gray-700 rounded-full text-xs">Unknown</span>';
        }
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active')->where('end_date', '>', Carbon::now());
    }

    public function scopeExpired($query)
    {
        return $query->where('status', 'expired')->orWhere('end_date', '<', Carbon::now());
    }

    public function scopeWillExpireSoon($query, $days = 7)
    {
        return $query->where('status', 'active')
            ->where('end_date', '>', Carbon::now())
            ->where('end_date', '<=', Carbon::now()->addDays($days));
    }
}
