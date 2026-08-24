<?php

namespace App\Models;

use App\Services\TutorContentVisibilityService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuestionBank extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected static function booted(): void
    {
        static::addGlobalScope('tutor-content-owner', function (Builder $query): void {
            $user = auth()->user();

            if (! app(TutorContentVisibilityService::class)->shouldScopeToOwner($user)) {
                return;
            }

            $query->where($query->qualifyColumn('created_by'), $user->id);
        });
    }

    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function questions()
    {
        return $this->hasMany(QuestionBankQuestion::class, 'question_bank_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
