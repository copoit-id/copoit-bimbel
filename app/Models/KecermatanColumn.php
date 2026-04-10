<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KecermatanColumn extends Model
{
    use HasFactory;

    protected $table = 'kecermatan_columns';
    protected $primaryKey = 'column_id';
    protected $guarded = ['column_id'];

    protected $casts = [
        'kolom_data' => 'array',
    ];

    public function tryout()
    {
        return $this->belongsTo(Tryout::class, 'tryout_id', 'tryout_id');
    }

    public function rows()
    {
        return $this->hasMany(KecermatanRow::class, 'column_id', 'column_id')->orderBy('row_number');
    }

    public function questions()
    {
        return $this->hasMany(KecermatanQuestion::class, 'column_id', 'column_id');
    }
}
