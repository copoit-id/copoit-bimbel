<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CertificateTemplate extends Model
{
    use HasFactory;

    protected $primaryKey = 'certificate_template_id';

    protected $fillable = [
        'client_profile_id',
        'name',
        'background_path',
        'layout',
        'is_active',
    ];

    protected $casts = [
        'layout' => 'array',
        'is_active' => 'boolean',
        'client_profile_id' => 'integer',
    ];

    public function clientProfile(): BelongsTo
    {
        return $this->belongsTo(ClientProfile::class);
    }

    public function tryouts(): HasMany
    {
        return $this->hasMany(Tryout::class, 'certificate_template_id', 'certificate_template_id');
    }
}
