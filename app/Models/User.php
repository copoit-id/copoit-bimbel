<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    private const URL_LIKE_PATTERN = '~(?:https?://\\S+|www\\.\\S+|\\b[a-z0-9][a-z0-9-]{1,61}\\.[a-z]{2,}(?:\\.[a-z]{2,})?\\b)~i';

    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    protected ?array $effectivePermissionSlugs = null;

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
            'admin_expires_at' => 'datetime',
        ];
    }

    public static function containsUrlLike(string $value): bool
    {
        return (bool) preg_match(self::URL_LIKE_PATTERN, $value);
    }

    public static function sanitizeName(string $name): string
    {
        $name = preg_replace('/[\\x00-\\x1F\\x7F]/', '', $name);
        $name = trim(preg_replace('/\\s+/', ' ', $name));

        if ($name === '') {
            return '';
        }

        $name = preg_replace(self::URL_LIKE_PATTERN, '', $name);
        $name = trim(preg_replace('/\\s+/', ' ', $name));

        return $name;
    }

    public static function obfuscateUrlLike(string $value): string
    {
        return preg_replace_callback(self::URL_LIKE_PATTERN, function ($matches) {
            return str_replace(['.', ':', '/'], ['[.]', '[:]', '[/]'], $matches[0]);
        }, $value);
    }

    public function getSafeNameForEmailAttribute(): string
    {
        $name = (string) ($this->attributes['name'] ?? '');
        if ($name === '') {
            return '';
        }

        return self::obfuscateUrlLike($name);
    }

    public function setNameAttribute($value): void
    {
        $this->attributes['name'] = self::sanitizeName((string) $value);
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

    public function roles()
    {
        return $this->belongsToMany(Role::class);
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

    public function isAdmin(): bool
    {
        return $this->canAccessAdminPanel();
    }

    public function canAccessAdminPanel(): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        if (!empty($this->getEffectivePermissionSlugs())) {
            return true;
        }

        // Backward compatibility for legacy users that might not have role pivot synced yet.
        return in_array($this->role, ['admin', 'admin_demo'], true);
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === 'super_admin';
    }

    public function isDemoAdmin(): bool
    {
        return $this->role === 'admin_demo';
    }

    public function hasPermission(string $feature, string $action): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        // Allow admin landing page once user is recognized as admin-panel user.
        if ($feature === 'dashboard' && $action === 'view' && $this->canAccessAdminPanel()) {
            return true;
        }

        $slug = $feature . '.' . $action;

        return in_array($slug, $this->getEffectivePermissionSlugs(), true);
    }

    public function getEffectivePermissionSlugs(): array
    {
        if ($this->effectivePermissionSlugs !== null) {
            return $this->effectivePermissionSlugs;
        }

        $this->loadMissing('roles.permissions');

        $slugs = $this->roles
            ->flatMap(function ($role) {
                return $role->permissions->pluck('slug');
            })
            ->unique()
            ->values()
            ->all();

        if (empty($slugs) && !empty($this->role)) {
            $fallbackRole = Role::query()
                ->where('slug', $this->role)
                ->with('permissions:id,slug')
                ->first();

            if ($fallbackRole) {
                $slugs = $fallbackRole->permissions
                    ->pluck('slug')
                    ->unique()
                    ->values()
                    ->all();
            }
        }

        $this->effectivePermissionSlugs = $slugs;

        return $this->effectivePermissionSlugs;
    }
}
