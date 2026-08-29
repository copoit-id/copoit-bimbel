<?php

namespace App\Models;

use App\Services\TutorContentVisibilityService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class TryoutDetail extends Model
{
    use HasFactory;

    protected $table = 'tryout_details';
    protected $primaryKey = 'tryout_detail_id';

    protected $fillable = [
        'tryout_id',
        'type_subtest',
        'material_category_id',
        'duration',
        'passing_score',
        'passing_type',
    ];

    protected $casts = [
        'duration' => 'decimal:2',
        'material_category_id' => 'integer',
        'passing_score' => 'decimal:2',
        'passing_type' => 'string',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('tutor-content-owner', function (Builder $query): void {
            if (! app(TutorContentVisibilityService::class)->shouldScopeToOwner(auth()->user())) {
                return;
            }

            $query->whereHas('tryout');
        });
    }

    public function tryout(): BelongsTo
    {
        return $this->belongsTo(Tryout::class, 'tryout_id', 'tryout_id');
    }

    public function questions(): HasMany
    {
        return $this->hasMany(Question::class, 'tryout_detail_id', 'tryout_detail_id');
    }

    public function materialCategory(): BelongsTo
    {
        return $this->belongsTo(MaterialCategory::class, 'material_category_id', 'category_id');
    }

    /**
     * Nama subtest untuk ditampilkan ke pengguna.
     *
     * Kode tetap dipakai sebagai identifier internal, sedangkan nama kategori
     * menjadi sumber tampilan agar perubahan nama langsung berlaku di semua
     * halaman yang memuat relasi materialCategory.
     */
    public function getDisplayNameAttribute(): string
    {
        $categoryName = trim((string) ($this->materialCategory?->name ?? ''));

        return $categoryName !== ''
            ? $categoryName
            : self::displayNameFromType($this->type_subtest);
    }

    public function getShortDisplayNameAttribute(): string
    {
        return Str::limit($this->display_name, 28, '…');
    }

    /**
     * Singkatan untuk badge kecil, dibentuk dari nama subtest saat ini.
     */
    public function getDisplayAbbreviationAttribute(): string
    {
        return self::abbreviationFromName($this->display_name);
    }

    public static function abbreviationFromName(?string $name): string
    {
        $words = collect(preg_split('/[^\pL\pN]+/u', trim((string) $name), -1, PREG_SPLIT_NO_EMPTY))
            ->take(4);

        $abbreviation = $words
            ->map(fn (string $word): string => Str::upper(Str::substr($word, 0, 1)))
            ->implode('');

        return $abbreviation !== '' ? $abbreviation : 'S';
    }

    public static function displayNameFromType(?string $type): string
    {
        $key = (string) Str::of((string) $type)->lower()->replaceMatches('/\s+/', ' ');

        return [
            'twk' => 'Tes Wawasan Kebangsaan',
            'tiu' => 'Tes Intelegensi Umum',
            'tkp' => 'Tes Karakteristik Pribadi',
            'tpa' => 'TPA',
            'tbi' => 'TBI',
            'tob' => 'TOB',
            'writing' => 'Writing Test',
            'reading' => 'Reading Comprehension',
            'listening' => 'Listening Test',
            'general' => 'General Test',
            'teknis' => 'Tes Teknis',
            'social culture' => 'Sosial-Kultural & Manajerial',
            'management' => 'Manajerial',
            'interview' => 'Wawancara',
            'word' => 'Microsoft Word',
            'excel' => 'Microsoft Excel',
            'ppt' => 'Microsoft PowerPoint',
            'penalaran_umum' => 'Penalaran Umum',
            'pengetahuan_umum' => 'Pengetahuan & Pemahaman Umum',
            'pengetahuan_kuantitatif' => 'Pengetahuan Kuantitatif',
            'pemahaman_bacaan_menulis' => 'Pemahaman Bacaan & Menulis',
            'literasi_bahasa_indonesia' => 'Literasi Bahasa Indonesia',
            'literasi_bahasa_inggris' => 'Literasi Bahasa Inggris',
            'penalaran_matematika' => 'Penalaran Matematika',
        ][$key] ?? Str::headline((string) $type);
    }
}
