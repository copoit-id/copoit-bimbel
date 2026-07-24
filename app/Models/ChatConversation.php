<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ChatConversation extends Model
{
    use HasFactory;
    use HasUlids;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'class_id',
        'student_user_id',
        'tutor_user_id',
        'tentor_id',
        'last_message_id',
        'last_message_at',
    ];

    protected $casts = [
        'last_message_at' => 'datetime',
    ];

    public function classRoom(): BelongsTo
    {
        return $this->belongsTo(ClassModel::class, 'class_id', 'class_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_user_id');
    }

    public function tutor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'tutor_user_id');
    }

    public function tentor(): BelongsTo
    {
        return $this->belongsTo(Tentor::class, 'tentor_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(ChatMessage::class, 'conversation_id');
    }

    public function latestMessage(): HasOne
    {
        return $this->hasOne(ChatMessage::class, 'id', 'last_message_id');
    }

    public function readStates(): HasMany
    {
        return $this->hasMany(ChatReadState::class, 'conversation_id');
    }

    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where(function (Builder $query) use ($userId) {
            $query->where('student_user_id', $userId)
                ->orWhere('tutor_user_id', $userId);
        });
    }

    public function isParticipant(User $user): bool
    {
        return in_array($user->id, [$this->student_user_id, $this->tutor_user_id], true);
    }

    public function isAccessibleBy(User $user): bool
    {
        if ((int) $this->student_user_id === (int) $user->id) {
            return $this->classRoom()->first()?->canUserAccess($user->id) ?? false;
        }

        return (int) $this->tutor_user_id === (int) $user->id
            && $this->tentor()
                ->where('user_id', $user->id)
                ->where('is_active', true)
                ->exists();
    }
}
