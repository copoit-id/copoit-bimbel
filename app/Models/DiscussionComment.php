<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Discussion;
use App\Models\User;

class DiscussionComment extends Model
{
    protected $table = 'discussion_comments';
    protected $guarded = ['discussion_comment_id'];
    protected $primaryKey = 'discussion_comment_id';


    public function discussion()
    {
        return $this->belongsTo(Discussion::class, 'discussion_id');
    }
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
