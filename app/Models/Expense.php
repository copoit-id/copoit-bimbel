<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Expense extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'amount',
        'spent_at',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'spent_at' => 'datetime',
        'amount' => 'decimal:0',
    ];

    public function author()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
