<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Models\DiscussionComment;

class Discussion extends Model
{
    protected $table = 'discussions';
    protected $guarded = ['discussion_id'];
    protected $primaryKey = 'discussion_id';

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
    public function comments()
    {
        return $this->hasMany(DiscussionComment::class, 'discussion_id');
    }
}
