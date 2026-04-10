<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KecermatanQuestion extends Model
{
    use HasFactory;

    protected $table = 'kecermatan_questions';
    protected $primaryKey = 'question_id';
    protected $guarded = ['question_id'];

    public function column()
    {
        return $this->belongsTo(KecermatanColumn::class, 'column_id', 'column_id');
    }

    public function row()
    {
        return $this->belongsTo(KecermatanRow::class, 'row_id', 'row_id');
    }
}
