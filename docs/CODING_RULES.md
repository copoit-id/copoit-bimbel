# CODING RULES - BIMBELHUB

> Dokumen ini adalah PRIMARY REFERENCE untuk semua development.
> Setiap kode yang ditulis HARUS mematuhi aturan di bawah ini.

---

## 1. FILE SYSTEM & NAMING CONVENTIONS

### 1.1 Case Sensitivity (CRITICAL)
- **SELALU** gunakan huruf kecil untuk nama folder dan file
- Linux (production) case-sensitive, macOS/Windows (local) case-insensitive
- ❌ `resources/views/components/ui/Button/`  
- ✅ `resources/views/components/ui/button/`

### 1.2 Folder Structure Components
```
resources/views/components/
├── ui/                    # Atomic components (huruf kecil semua)
│   ├── button/
│   │   └── index.blade.php
│   ├── card/
│   │   ├── index.blade.php
│   │   ├── header.blade.php
│   │   └── footer.blade.php
│   ├── input/
│   ├── badge/
│   └── modal/
├── layout/                # Layout components
│   ├── container/
│   ├── page-header/       # Gunakan kebab-case untuk nama folder
│   ├── navbar/
│   └── sidebar/
├── form/                  # Form-specific components
└── dashboard/             # Feature-specific components
```

### 1.3 Component Naming
- Component tag: `<x-ui.button>` (dot notation, huruf kecil)
- Sub-component: `<x-ui.card.header>` (dot notation)
- Props: camelCase (`$fullWidth`, `$iconPosition`)
- Variant/Size: snake_case atau kebab-case (`primary`, `ghost`, `icon-only`)

---

## 2. LARAVEL BEST PRACTICES

### 2.1 Controllers
```php
<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Package;
use App\Services\PackageService;  // Gunakan Service untuk logic bisnis
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PackageController extends Controller
{
    public function __construct(
        private PackageService $packageService  // Dependency Injection
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): View
    {
        // SELALU eager load relasi
        $packages = Package::with(['category', 'author'])
            ->when($request->search, fn($q, $search) => 
                $q->where('name', 'like', "%{$search}%")
            )
            ->latest()
            ->paginate(20);  // Gunakan pagination, jangan get() all

        return view('admin.packages.index', compact('packages'));
    }

    /**
     * Store a newly created resource.
     */
    public function store(Request $request): RedirectResponse
    {
        // Validasi di controller atau FormRequest
        $validated = $request->validated();

        try {
            DB::transaction(function () use ($validated) {
                $this->packageService->create($validated);
            });

            return redirect()
                ->route('admin.packages.index')
                ->with('success', 'Package created successfully');
        } catch (\Exception $e) {
            report($e);  // Log error
            
            return back()
                ->withInput()
                ->with('error', 'Failed to create package: ' . $e->getMessage());
        }
    }
}
```

**Rules:**
- Gunakan `Route Model Binding` untuk parameter model
- Return type declaration wajib (`: View`, `: RedirectResponse`)
- Gunakan `DB::transaction()` untuk operasi multiple table
- SELALU redirect dengan flash message (success/error)

---

### 2.2 Models

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

class Package extends Model
{
    use HasFactory;

    protected $primaryKey = 'package_id';  // Jika bukan 'id'
    
    protected $fillable = [
        'name',
        'description',
        'category_id',
        'price',
        'is_active',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'is_active' => 'boolean',
        'published_at' => 'datetime',
    ];

    protected $with = [];  // Hati-hati dengan eager load default

    // === RELATIONSHIPS ===
    
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function questions(): HasMany
    {
        return $this->hasMany(Question::class, 'package_id');
    }

    // === SCOPES ===

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeByCategory(Builder $query, int $categoryId): Builder
    {
        return $query->where('category_id', $categoryId);
    }

    public function scopeSearch(Builder $query, string $term): Builder
    {
        return $query->where(function ($q) use ($term) {
            $q->where('name', 'like', "%{$term}%")
              ->orWhere('description', 'like', "%{$term}%");
        });
    }

    // === ACCESSORS & MUTATORS ===

    protected function name(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => ucwords($value),
            set: fn ($value) => strtolower($value),
        );
    }

    // === CUSTOM METHODS ===

    public function toggleActive(): void
    {
        $this->update(['is_active' => !$this->is_active]);
    }
}
```

**Rules:**
- SELALU definisikan tipe relasi (`BelongsTo`, `HasMany`, dll)
- Gunakan `Scope` untuk query yang sering dipakai
- Cast tipe data yang tepat di `$casts`
- Minimal fillable, hindari `$guarded = []`

---

### 2.3 Route Definition
```php
<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\PackageController;

// Group by middleware dan prefix
Route::middleware(['auth', 'role:admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        
        // Dashboard
        Route::get('/dashboard', [DashboardController::class, 'index'])
            ->name('dashboard');
        
        // Resource routes (7 default actions)
        Route::resource('packages', PackageController::class);
        
        // Custom routes - letakkan SEBELUM atau SESUDAH resource
        Route::post('packages/{package}/toggle', [PackageController::class, 'toggle'])
            ->name('packages.toggle');
        
        // API-like routes untuk AJAX
        Route::prefix('api')->name('api.')->group(function () {
            Route::get('packages/search', [PackageController::class, 'search'])
                ->name('packages.search');
        });
    });
```

**Rules:**
- Gunakan `Route::resource()` untuk CRUD standar
- Route name HARUS konsisten (prefix, nested)
- Group by middleware, prefix, dan name
- Contoh: `admin.packages.index`, `admin.packages.store`

---

### 2.4 Blade Components

```blade
{{-- resources/views/components/ui/button/index.blade.php --}}
@props([
    'variant' => 'primary',     // primary | secondary | outline | ghost | danger
    'size' => 'md',             // sm | md | lg | icon
    'type' => 'button',         // button | submit | reset
    'href' => null,             // string | null
    'icon' => null,             // string | null (icon class)
    'iconPosition' => 'left',   // left | right
    'disabled' => false,
    'loading' => false,
    'fullWidth' => false,
])

@php
$baseClasses = 'inline-flex items-center justify-center gap-2 font-medium rounded-lg transition-all duration-200';

$variantClasses = [
    'primary' => 'bg-primary text-white hover:bg-primary/90',
    'danger' => 'bg-red text-white hover:bg-red/90',
][$variant] ?? 'bg-primary text-white';

$classes = implode(' ', [
    $baseClasses,
    $variantClasses,
    $attributes->get('class', ''),
]);
@endphp

@if($href && !$disabled)
    <a href="{{ $href }}" class="{{ $classes }}" {{ $attributes->except(['class', 'href']) }}>
        {{ $slot }}
    </a>
@else
    <button type="{{ $type }}" class="{{ $classes }}" {{ $disabled ? 'disabled' : '' }}>
        {{ $slot }}
    </button>
@endif
```

**Penggunaan:**
```blade
<x-ui.button variant="primary" size="md" href="/login">
    Login
</x-ui.button>

<x-ui.button variant="danger" size="sm" icon="ri-delete-bin-line">
    Delete
</x-ui.button>
```

---

## 3. DATABASE & QUERY OPTIMIZATION

### 3.1 Eager Loading (Hindari N+1)
```php
// ❌ BAD - N+1 Query
$packages = Package::all();
foreach ($packages as $package) {
    echo $package->category->name;  // Query tambahan setiap iterasi!
}

// ✅ GOOD - Eager Load
$packages = Package::with('category')->get();
foreach ($packages as $package) {
    echo $package->category->name;  // Sudah loaded
}

// Multiple relationships
$packages = Package::with(['category', 'author', 'tags'])->get();

// Nested relationships
$packages = Package::with(['category.parent', 'author.profile'])->get();

// Eager load dengan kondisi
$packages = Package::with(['questions' => function ($query) {
    $query->where('is_active', true);
}])->get();
```

### 3.2 Select Specific Columns
```php
// ❌ BAD - Select semua kolom
Package::all();

// ✅ GOOD - Select yang diperlukan saja
Package::select('package_id', 'name', 'price')->get();

// Dengan eager load + select
Package::select('package_id', 'name', 'category_id')
    ->with(['category:id,name'])  // Select kolom category juga
    ->get();
```

### 3.3 Pagination
```php
// ❌ BAD - Ambil semua data
Package::all();

// ✅ GOOD - Paginate
Package::paginate(20);           // Pagination default
Package::simplePaginate(20);     // Simple pagination (lebih cepat)
Package::cursorPaginate(20);     // Cursor pagination (tercepat untuk data besar)
```

### 3.4 Chunk untuk Data Besar
```php
// ❌ BAD - Memory habis
Package::all()->each(function ($package) {
    // Process
});

// ✅ GOOD - Chunk
Package::chunk(100, function ($packages) {
    foreach ($packages as $package) {
        // Process
    }
});

// ✅ BETTER - Lazy (Laravel 8+)
foreach (Package::lazy() as $package) {
    // Process
}
```

### 3.5 Indexes
```php
// Migration dengan index
Schema::table('packages', function (Blueprint $table) {
    $table->index('category_id');
    $table->index(['is_active', 'created_at']);  // Composite index
    $table->unique('slug');
    $table->fullText('description');  // Untuk search
});
```

### 3.6 Query Caching
```php
// Cache query result
$packages = Cache::remember('packages.active', 3600, function () {
    return Package::active()->get();
});

// Cache dengan tags (untuk flush selective)
$packages = Cache::tags(['packages'])->remember('packages.all', 3600, function () {
    return Package::all();
});
```

---

## 4. MIGRATIONS (PRODUCTION SAFE)

### 4.1 Aturan Wajib
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ✅ SELALU cek sebelum create table
        if (!Schema::hasTable('packages')) {
            Schema::create('packages', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->timestamps();
            });
        }

        // ✅ SELALU cek sebelum add column
        Schema::table('packages', function (Blueprint $table) {
            if (!Schema::hasColumn('packages', 'is_featured')) {
                $table->boolean('is_featured')->default(false);
            }
        });

        // ✅ SELALU cek sebelum drop column
        Schema::table('packages', function (Blueprint $table) {
            if (Schema::hasColumn('packages', 'old_column')) {
                // Try-catch untuk foreign key
                try {
                    $table->dropForeign(['old_column']);
                } catch (\Exception $e) {
                    // Foreign key mungkin sudah tidak ada
                }
                $table->dropColumn('old_column');
            }
        });
    }

    public function down(): void
    {
        // Reverse operasi dengan pengecekan juga
        Schema::table('packages', function (Blueprint $table) {
            if (Schema::hasColumn('packages', 'is_featured')) {
                $table->dropColumn('is_featured');
            }
        });
    }
};
```

### 4.2 Checklist Migration Production
- [ ] Cek `hasTable()` sebelum create
- [ ] Cek `hasColumn()` sebelum add/drop column
- [ ] Try-catch untuk dropForeign
- [ ] Method `down()` harus reverse dengan aman
- [ ] Test di local dengan fresh migrate

---

## 5. PERFORMANCE CHECKLIST

### 5.1 Sebelum Deploy
- [ ] Cek N+1 dengan Laravel Debugbar / Telescope
- [ ] Pastikan semua query pakai eager load
- [ ] Pagination untuk list > 50 items
- [ ] Index untuk kolom yang sering di-query
- [ ] Cache untuk data yang jarang berubah

### 5.2 Anti Patterns
```php
// ❌ N+1
Package::all()->map(fn($p) => $p->category->name);

// ❌ Query dalam Loop
foreach ($users as $user) {
    $orders = Order::where('user_id', $user->id)->get();  // NO!
}

// ❌ Select *
Model::all();

// ❌ No Pagination
Model::get();  // Kalau data banyak = crash

// ❌ Missing Index
where('email', $email)->first();  // email harus indexed
```

---

## 6. SECURITY

### 6.1 Mass Assignment Protection
```php
// Model
protected $fillable = ['name', 'email'];  // ✅ White list
protected $guarded = [];                   // ❌ NEVER di production
```

### 6.2 SQL Injection Prevention
```php
// ✅ Gunakan Query Builder atau Eloquent
User::where('email', $request->email)->first();

// ❌ Jangan concat string
DB::select("SELECT * FROM users WHERE email = '$request->email'");
```

### 6.3 XSS Protection
```blade
{{-- ✅ Escaped otomatis --}}
{{ $user->name }}

{{-- ❌ Raw - hanya untuk trusted content --}}
{!! $user->bio !!}
```

---

## 7. TESTING

```php
<?php

use App\Models\Package;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('can create a package', function () {
    $response = $this->postJson('/api/packages', [
        'name' => 'Test Package',
        'price' => 100000,
    ]);

    $response->assertCreated()
        ->assertJson(['name' => 'Test Package']);
    
    $this->assertDatabaseHas('packages', [
        'name' => 'Test Package',
    ]);
});

it('prevents n+1 queries', function () {
    Package::factory()->count(10)->create();
    
    DB::enableQueryLog();
    
    $packages = Package::with('category')->get();
    foreach ($packages as $package) {
        $package->category->name;
    }
    
    expect(DB::getQueryLog())->toHaveCount(2);  // 1 untuk packages, 1 untuk categories
});
```

---

## 8. GIT WORKFLOW

### 8.1 Commit Message
```
feat: add package management
fix: resolve n+1 in dashboard
refactor: optimize query with eager load
docs: update api documentation
test: add package controller tests
```

### 8.2 Branch Naming
```
feature/package-management
fix/n1-query-dashboard
hotfix/critical-bug-production
```

---

## QUICK REFERENCE

| Aspek | Rule |
|-------|------|
| Folder Components | Huruf kecil: `button/`, `card/` |
| Component Tag | `<x-ui.button>` (lowercase) |
| Model | Eager load, scope, fillable |
| Controller | Return type, service injection |
| Route | Resource, name prefix |
| Query | with(), paginate(), select() |
| Migration | hasTable(), hasColumn(), try-catch |

---

**INGAT: Local (macOS) ≠ Production (Linux). SELALU gunakan huruf kecil untuk file/folder!**
