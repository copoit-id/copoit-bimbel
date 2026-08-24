<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    protected $table = 'roles';
    protected $guarded = ['id'];

    public static function adminCanViewFeature(string $feature): bool
    {
        return static::query()
            ->where('slug', 'admin')
            ->whereHas('permissions', function ($query) use ($feature) {
                $query->where('slug', $feature . '.view');
            })
            ->exists();
    }

    public function permissions()
    {
        return $this->belongsToMany(Permission::class);
    }

    public function users()
    {
        return $this->belongsToMany(User::class);
    }
}
