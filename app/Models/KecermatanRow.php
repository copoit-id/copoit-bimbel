<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KecermatanRow extends Model
{
    use HasFactory;

    protected $table = 'kecermatan_rows';
    protected $primaryKey = 'row_id';
    protected $guarded = ['row_id'];

    public function column()
    {
        return $this->belongsTo(KecermatanColumn::class, 'column_id', 'column_id');
    }

    public function questions()
    {
        return $this->hasMany(KecermatanQuestion::class, 'row_id', 'row_id');
    }
}
