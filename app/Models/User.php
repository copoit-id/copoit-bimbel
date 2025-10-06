<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $guarded = [
        'id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    // Relationships
    public function userAnswers()
    {
        return $this->hasMany(UserAnswer::class, 'user_id', 'id');
    }

    public function userPackageAccess()
    {
        return $this->hasMany(UserPackageAcces::class, 'user_id', 'id');
    }

    public function payments()
    {
        return $this->hasMany(Payment::class, 'user_id', 'id');
    }

    public function certificates()
    {
        return $this->hasMany(Certificate::class, 'user_id', 'id');
    }

    // Helper methods
    public function hasActivePackageAccess($packageId)
    {
        return $this->userPackageAccess()
            ->where('package_id', $packageId)
            ->where('status', 'active')
            ->where(function ($query) {
                $query->whereNull('end_date')
                    ->orWhere('end_date', '>', now());
            })
            ->exists();
    }

    public function getCompletedTryoutsCount()
    {
        return $this->userAnswers()
            ->where('status', 'completed')
            ->count();
    }

    public function getAverageScore()
    {
        return $this->userAnswers()
            ->where('status', 'completed')
            ->whereNotNull('score')
            ->avg('score') ?? 0;
    }

    // Add helper method to check if user is admin based on migration structure
    public function isAdmin()
    {
        return $this->role === 'admin';
    }
}
