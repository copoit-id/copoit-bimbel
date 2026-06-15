<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TesKoranSheet extends Model
{
    protected $fillable = [
        'tes_koran_id',
        'sheet_order',
        'name',
        'number_type',
        'operation_type',
        'column_duration_seconds',
        'columns_count',
        'rows_count',
    ];

    protected $casts = [
        'sheet_order' => 'integer',
        'column_duration_seconds' => 'integer',
        'columns_count' => 'integer',
        'rows_count' => 'integer',
    ];

    public function tesKoran(): BelongsTo
    {
        return $this->belongsTo(TesKoran::class);
    }
}
