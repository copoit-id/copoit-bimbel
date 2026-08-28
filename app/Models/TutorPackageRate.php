<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TutorPackageRate extends Model
{
    use HasFactory;

    protected $fillable = [
        'tentor_id',
        'package_id',
        'amount',
    ];

    protected $casts = [
        'amount' => 'decimal:0',
    ];

    public function tentor(): BelongsTo
    {
        return $this->belongsTo(Tentor::class);
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class, 'package_id', 'package_id');
    }
}
