# Flow Implementasi Fitur Baru: Master Materi & Restruktur Menu User

## Ringkasan Perubahan

### 1. Master Materi (Terpisah dari Tryout)
Materi akan menjadi entitas standalone yang berisi:
- **Video** - Video pembelajaran
- **Belajar (Materi/PDF)** - Dokumen pembelajaran
- **Live Session/Kelas** - Sudah ada (ClassModel)

### 2. Perbedaan Materi vs Tryout
| Aspek | Materi | Tryout |
|-------|--------|--------|
| Tujuan | Pembelajaran | Evaluasi/tes |
| Konten | Video, PDF, Live | Soal dan jawaban |
| Akses | Langsung/bertahap | Langsung |
| Hasil | Progress belajar | Score/ranking |

### 3. Restruktur Menu User (Baru)
```
Dashboard
├── Paket (Step by Step Terstruktur)
│   └── [Paket Aktif User]
│       ├── Materi (terstruktur)
│       ├── Tryout (terstruktur)
│       └── Live Session (terstruktur)
├── Materi (Standalone)
│   ├── Video
│   ├── Belajar (PDF/Dokumen)
│   └── Live Session
├── Tryout (Standalone)
└── Event Gratis
```

---

## 1. Database Design

### 1.1 Tabel Baru

#### A. `materials` - Master Materi
```sql
create_materials_table:
- material_id (PK)
- title (string) - Judul materi
- description (text, nullable) - Deskripsi
- type (enum: 'video', 'document', 'live_session') - Tipe materi
- content_url (string) - URL video atau PDF
- thumbnail_url (string, nullable) - Thumbnail
- duration_minutes (integer, nullable) - Durasi dalam menit
- is_active (boolean, default: true)
- order_number (integer, default: 0) - Urutan tampilan
- metadata (json, nullable) - Data tambahan
- created_by (FK to users)
- timestamps
```

#### B. `material_categories` - Kategori Materi
```sql
create_material_categories_table:
- category_id (PK)
- name (string)
- description (text, nullable)
- icon (string, nullable)
- order_number (integer, default: 0)
- is_active (boolean, default: true)
- timestamps
```

#### C. `material_category_pivot` - Relasi Materi-Kategori (Many-to-Many)
```sql
create_material_category_pivot_table:
- id (PK)
- material_id (FK)
- category_id (FK)
- timestamps
```

#### D. `user_material_access` - Akses User ke Materi (Standalone)
```sql
create_user_material_access_table:
- user_material_access_id (PK)
- user_id (FK)
- material_id (FK)
- access_type (enum: 'free', 'purchased', 'subscription') - Cara dapat akses
- access_source (enum: 'direct', 'package') - Dari mana aksesnya
- source_id (integer, nullable) - ID package jika dari package
- started_at (datetime, nullable)
- completed_at (datetime, nullable)
- progress_percentage (integer, default: 0) - 0-100
- status (enum: 'not_started', 'in_progress', 'completed')
- timestamps
```

#### E. `material_progress_logs` - Log Progress Belajar
```sql
create_material_progress_logs_table:
- log_id (PK)
- user_id (FK)
- material_id (FK)
- event_type (enum: 'started', 'paused', 'resumed', 'completed', 'viewed')
- progress_seconds (integer, nullable) - Untuk video
- metadata (json, nullable) - Data tambahan
- ip_address (string, nullable)
- user_agent (text, nullable)
- timestamps
```

#### F. `package_materials` - Relasi Package dengan Materi (Polymorphic seperti DetailPackage)
```sql
create_package_materials_table:
- package_material_id (PK)
- package_id (FK)
- material_id (FK)
- section_name (string, nullable) - Nama section (misal: "Minggu 1")
- order_number (integer, default: 0)
- is_required (boolean, default: true) - Wajib dikerjakan?
- unlock_condition (json, nullable) - Syarat membuka
- timestamps
```

### 1.2 Modifikasi Tabel Existing

#### A. `detail_packages` (Tambah tipe 'material')
Tabel sudah ada dengan polymorphic `detailable`. Cukup pastikan `Material` model menggunakan morph map yang benar.

#### B. `user_package_acces` (Tambah kolom)
```sql
Tambahkan:
- access_pattern (enum: 'package_only', 'standalone', 'hybrid') 
  default: 'package_only'
```

---

## 2. Model Relationships

### 2.1 Model Baru

```php
// app/Models/Material.php
class Material extends Model
{
    protected $primaryKey = 'material_id';
    protected $guarded = ['material_id'];
    
    protected $casts = [
        'is_active' => 'boolean',
        'metadata' => 'array',
    ];
    
    // Relationships
    public function categories()
    {
        return $this->belongsToMany(MaterialCategory::class, 'material_category_pivot', 'material_id', 'category_id');
    }
    
    public function userAccess()
    {
        return $this->hasMany(UserMaterialAccess::class, 'material_id');
    }
    
    public function progressLogs()
    {
        return $this->hasMany(MaterialProgressLog::class, 'material_id');
    }
    
    public function packages()
    {
        return $this->belongsToMany(Package::class, 'package_materials')
            ->withPivot(['section_name', 'order_number', 'is_required', 'unlock_condition']);
    }
    
    // Polymorphic untuk detail_packages (seperti Tryout & ClassModel)
    public function detailPackages()
    {
        return $this->morphMany(DetailPackage::class, 'detailable');
    }
    
    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
    
    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }
}
```

```php
// app/Models/MaterialCategory.php
class MaterialCategory extends Model
{
    protected $primaryKey = 'category_id';
    protected $guarded = ['category_id'];
    
    protected $casts = [
        'is_active' => 'boolean',
    ];
    
    public function materials()
    {
        return $this->belongsToMany(Material::class, 'material_category_pivot', 'category_id', 'material_id');
    }
}
```

```php
// app/Models/UserMaterialAccess.php
class UserMaterialAccess extends Model
{
    protected $table = 'user_material_access';
    protected $primaryKey = 'user_material_access_id';
    protected $guarded = ['user_material_access_id'];
    
    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'progress_percentage' => 'integer',
    ];
    
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    
    public function material()
    {
        return $this->belongsTo(Material::class, 'material_id');
    }
}
```

```php
// app/Models/MaterialProgressLog.php
class MaterialProgressLog extends Model
{
    protected $table = 'material_progress_logs';
    protected $primaryKey = 'log_id';
    protected $guarded = ['log_id'];
    
    protected $casts = [
        'metadata' => 'array',
    ];
    
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    
    public function material()
    {
        return $this->belongsTo(Material::class, 'material_id');
    }
}
```

```php
// app/Models/PackageMaterial.php (Pivot dengan data tambahan)
class PackageMaterial extends Model
{
    protected $table = 'package_materials';
    protected $primaryKey = 'package_material_id';
    protected $guarded = ['package_material_id'];
    
    protected $casts = [
        'is_required' => 'boolean',
        'unlock_condition' => 'array',
    ];
    
    public function package()
    {
        return $this->belongsTo(Package::class, 'package_id');
    }
    
    public function material()
    {
        return $this->belongsTo(Material::class, 'material_id');
    }
}
```

### 2.2 Update Model Existing

```php
// app/Models/Package.php - Tambahkan relasi
class Package extends Model
{
    // ... existing code ...
    
    // Relasi ke materials melalui package_materials
    public function materials()
    {
        return $this->belongsToMany(Material::class, 'package_materials')
            ->withPivot(['section_name', 'order_number', 'is_required', 'unlock_condition'])
            ->orderBy('package_materials.order_number');
    }
    
    // Polymorphic melalui detail_packages (sama seperti tryouts & classes)
    public function materialsThroughDetail()
    {
        return $this->hasManyThrough(
            Material::class,
            DetailPackage::class,
            'package_id',
            'material_id',
            'package_id',
            'detailable_id'
        )->where('detail_packages.detailable_type', Material::class);
    }
}
```

```php
// app/Models/User.php - Tambahkan relasi
class User extends Model
{
    // ... existing code ...
    
    public function materialAccess()
    {
        return $this->hasMany(UserMaterialAccess::class, 'user_id');
    }
    
    // Helper untuk cek akses ke material
    public function hasMaterialAccess($materialId): bool
    {
        return $this->materialAccess()
            ->where('material_id', $materialId)
            ->where('status', '!=', 'not_started')
            ->exists();
    }
}
```

---

## 3. Routes Structure

### 3.1 Routes User Baru

```php
// routes/web.php - Bagian User (Route::prefix('user')->middleware('auth'))

// ========== PAKET (STEP BY STEP - TERSTRUKTUR) ==========
// [Existing routes tetap ada, tapi dirombak UI-nya]
Route::prefix('paket-saya')->group(function () {
    Route::get('/', [PackageController::class, 'myPackages'])->name('user.package.my');
    Route::get('/{package_id}', [PackageController::class, 'showPackage'])->name('user.package.show');
    Route::get('/{package_id}/materi', [PackageController::class, 'packageMaterials'])->name('user.package.materials');
    Route::get('/{package_id}/tryout', [PackageController::class, 'packageTryouts'])->name('user.package.tryouts');
    Route::get('/{package_id}/live-session', [PackageController::class, 'packageLiveSessions'])->name('user.package.live-sessions');
});

// ========== MATERI (STANDALONE) ==========
Route::prefix('materi')->group(function () {
    // List & Kategori
    Route::get('/', [MaterialController::class, 'index'])->name('user.material.index');
    Route::get('/kategori/{category_id}', [MaterialController::class, 'byCategory'])->name('user.material.category');
    
    // By Type
    Route::get('/video', [MaterialController::class, 'videos'])->name('user.material.videos');
    Route::get('/belajar', [MaterialController::class, 'documents'])->name('user.material.documents');
    Route::get('/live-session', [MaterialController::class, 'liveSessions'])->name('user.material.live-sessions');
    
    // Detail & Akses
    Route::get('/{material_id}', [MaterialController::class, 'show'])->name('user.material.show');
    Route::post('/{material_id}/start', [MaterialController::class, 'start'])->name('user.material.start');
    Route::post('/{material_id}/progress', [MaterialController::class, 'updateProgress'])->name('user.material.progress');
    Route::post('/{material_id}/complete', [MaterialController::class, 'complete'])->name('user.material.complete');
});

// ========== TRYOUT (STANDALONE) ==========
Route::prefix('tryout')->group(function () {
    // List semua tryout yang bisa diakses
    Route::get('/', [TryoutController::class, 'list'])->name('user.tryout.list');
    Route::get('/saya', [TryoutController::class, 'myTryouts'])->name('user.tryout.my');
    Route::get('/riwayat', [TryoutController::class, 'history'])->name('user.tryout.history');
    
    // Detail & Akses
    Route::get('/{tryout_id}', [TryoutController::class, 'show'])->name('user.tryout.show');
    Route::get('/{tryout_id}/lobby', [TryoutController::class, 'lobby'])->name('user.tryout.lobby');
    // ... existing tryout routes tetap berfungsi
});
```

### 3.2 Routes Admin Baru

```php
// routes/web.php - Bagian Admin (Route::prefix('admin'))

// ========== MANAJEMEN MATERI ==========
Route::prefix('materi')->name('material.')->group(function () {
    Route::get('/', [MaterialManagementController::class, 'index'])->name('index');
    Route::get('/create', [MaterialManagementController::class, 'create'])->name('create');
    Route::post('/', [MaterialManagementController::class, 'store'])->name('store');
    Route::get('/{material}/edit', [MaterialManagementController::class, 'edit'])->name('edit');
    Route::put('/{material}', [MaterialManagementController::class, 'update'])->name('update');
    Route::delete('/{material}', [MaterialManagementController::class, 'destroy'])->name('destroy');
    Route::post('/{material}/toggle', [MaterialManagementController::class, 'toggle'])->name('toggle');
    
    // Kategori
    Route::prefix('kategori')->name('category.')->group(function () {
        Route::get('/', [MaterialCategoryController::class, 'index'])->name('index');
        Route::post('/', [MaterialCategoryController::class, 'store'])->name('store');
        Route::put('/{category}', [MaterialCategoryController::class, 'update'])->name('update');
        Route::delete('/{category}', [MaterialCategoryController::class, 'destroy'])->name('destroy');
    });
});

// Update Package Management untuk include materi
Route::prefix('paket')->name('package.')->group(function () {
    // ... existing routes ...
    
    // Material dalam Package
    Route::get('/{package}/materi', [AdminPackageController::class, 'indexMaterials'])->name('materials.index');
    Route::post('/{package}/materi', [AdminPackageController::class, 'attachMaterial'])->name('materials.attach');
    Route::put('/{package}/materi/{material}', [AdminPackageController::class, 'updateMaterialPivot'])->name('materials.update');
    Route::delete('/{package}/materi/{material}', [AdminPackageController::class, 'detachMaterial'])->name('materials.detach');
    Route::post('/{package}/materi/reorder', [AdminPackageController::class, 'reorderMaterials'])->name('materials.reorder');
});
```

---

## 4. Controller Structure

### 4.1 Controller Baru (User)

```php
// app/Http/Controllers/user/MaterialController.php
class MaterialController extends Controller
{
    /**
     * Halaman utama materi - menampilkan kategori & featured materials
     */
    public function index()
    {
        // Get materials yang bisa diakses user (free atau sudah beli)
        // Group by type (video, document, live_session)
    }
    
    /**
     * List video materials
     */
    public function videos()
    {
        // Filter type = 'video'
    }
    
    /**
     * List document/PDF materials
     */
    public function documents()
    {
        // Filter type = 'document'
    }
    
    /**
     * List live sessions (dari ClassModel yang bisa standalone)
     */
    public function liveSessions()
    {
        // Get classes yang bisa diakses standalone
    }
    
    /**
     * Detail materi & cek akses
     */
    public function show($material_id)
    {
        // Cek apakah user punya akses
        // Jika tidak, tampilkan halaman preview dengan tombol beli/berlangganan
    }
    
    /**
     * Mulai belajar - catat log
     */
    public function start($material_id)
    {
        // Create UserMaterialAccess jika belum ada
        // Log event 'started'
    }
    
    /**
     * Update progress (untuk video)
     */
    public function updateProgress(Request $request, $material_id)
    {
        // Update progress_percentage
        // Log event 'viewed' atau 'paused'
    }
    
    /**
     * Tandai selesai
     */
    public function complete($material_id)
    {
        // Update status = 'completed'
        // Set completed_at
    }
}
```

### 4.2 Controller Baru (Admin)

```php
// app/Http/Controllers/admin/MaterialManagementController.php
class MaterialManagementController extends Controller
{
    public function index() { /* List semua materi */ }
    public function create() { /* Form tambah */ }
    public function store(Request $request) { /* Simpan */ }
    public function edit(Material $material) { /* Form edit */ }
    public function update(Request $request, Material $material) { /* Update */ }
    public function destroy(Material $material) { /* Hapus */ }
    public function toggle(Material $material) { /* Toggle aktif/nonaktif */ }
}
```

```php
// app/Http/Controllers/admin/MaterialCategoryController.php
class MaterialCategoryController extends Controller
{
    public function index() { /* List kategori */ }
    public function store(Request $request) { /* Simpan */ }
    public function update(Request $request, MaterialCategory $category) { /* Update */ }
    public function destroy(MaterialCategory $category) { /* Hapus */ }
}
```

### 4.3 Update Controller Existing

```php
// app/Http/Controllers/user/PackageController.php - Tambahkan methods
class PackageController extends Controller
{
    // ... existing code ...
    
    /**
     * Halaman paket saya (step by step view)
     */
    public function myPackages()
    {
        // Tampilkan paket aktif dengan struktur step by step
    }
    
    /**
     * Detail paket dengan urutan belajar
     */
    public function showPackage($package_id)
    {
        // Tampilkan struktur: 
        // Section 1: Materi 1 -> Materi 2 -> Live Session 1 -> Tryout 1
        // Section 2: Materi 3 -> Live Session 2 -> Tryout 2
        // dengan progress tracking
    }
    
    /**
     * Materi dalam paket (terstruktur)
     */
    public function packageMaterials($package_id)
    {
        // List materi dalam paket dengan urutan
    }
    
    /**
     * Tryout dalam paket (terstruktur)
     */
    public function packageTryouts($package_id)
    {
        // List tryout dalam paket dengan urutan
    }
}
```

---

## 5. View Structure

### 5.1 Restruktur Sidebar User

```blade
{{-- resources/views/user/components/sidebar.blade.php (Updated) --}}

<!-- HOME -->
<p class="section-label">Home</p>
<ul>
    <li><a href="{{ route('user.dashboard.index') }}">Dashboard</a></li>
    <li><a href="{{ route('user.package.index') }}">Beli Paket</a></li>
    <li><a href="{{ route('user.event.index') }}">Event Gratis</a></li>
</ul>

<!-- PEMBELAJARAN -->
<p class="section-label">Pembelajaran</p>
<ul>
    {{-- Paket Saya - Step by Step --}}
    <li>
        <a href="{{ route('user.package.my') }}" class="{{ active }}"">
            <i class="ri-stack-line"></i>
            <span>Paket Saya</span>
        </a>
        {{-- Dropdown paket aktif --}}
    </li>
    
    {{-- Materi - Standalone --}}
    <li>
        <button data-collapse-toggle="dropdown-materi">
            <i class="ri-book-open-line"></i>
            <span>Materi</span>
        </button>
        <ul id="dropdown-materi">
            <li><a href="{{ route('user.material.videos') }}">Video</a></li>
            <li><a href="{{ route('user.material.documents') }}">Belajar (PDF)</a></li>
            <li><a href="{{ route('user.material.live-sessions') }}">Live Session</a></li>
        </ul>
    </li>
    
    {{-- Tryout - Standalone --}}
    <li>
        <a href="{{ route('user.tryout.list') }}">
            <i class="ri-file-list-3-line"></i>
            <span>Tryout</span>
        </a>
    </li>
</ul>

<!-- LAINNYA -->
<p class="section-label">Lainnya</p>
<ul>
    <li><a href="{{ route('user.help.index') }}">Bantuan</a></li>
    <li><a href="{{ route('user.certificate.validation') }}">Validasi Sertifikat</a></li>
</ul>
```

### 5.2 View Baru User

```
resources/views/user/pages/material/
├── index.blade.php              # Halaman utama materi
├── videos.blade.php             # List video
├── documents.blade.php          # List dokumen/PDF
├── live-sessions.blade.php      # List live session
├── show-video.blade.php         # Player video
├── show-document.blade.php      # Viewer PDF/Dokumen
└── show-live.blade.php          # Detail live session

resources/views/user/pages/package/
├── my-packages.blade.php        # Paket saya (step by step)
├── package-detail.blade.php     # Detail paket dengan progress
├── package-materials.blade.php  # Materi dalam paket
└── package-tryouts.blade.php    # Tryout dalam paket
```

### 5.3 View Baru Admin

```
resources/views/admin/pages/material/
├── index.blade.php              # List materi
├── create.blade.php             # Form tambah
├── edit.blade.php               # Form edit
└── partials/
    ├── _video-form.blade.php    # Form khusus video
    └── _document-form.blade.php # Form khusus dokumen

resources/views/admin/pages/material-category/
├── index.blade.php              # List kategori
└── _modal-form.blade.php        # Modal form
```

---

## 6. Implementation Phases

### Phase 1: Database & Model (1-2 hari)
1. Buat migration files untuk tabel baru
2. Buat model Model baru (Material, MaterialCategory, UserMaterialAccess, dll)
3. Update model existing (Package, User, ClassModel)
4. Run migration

### Phase 2: Admin Panel (3-4 hari)
1. CRUD Kategori Materi
2. CRUD Master Materi (Video, Document)
3. Integrasi Materi ke Package Management
4. Integrasi ClassModel (Live Session) ke Materi

### Phase 3: User - Materi Standalone (3-4 hari)
1. Controller & Routes untuk Materi
2. View list materi (Video, Dokumen, Live Session)
3. View detail & player materi
4. Progress tracking system

### Phase 4: User - Restruktur Menu & Paket (3-4 hari)
1. Update sidebar dengan struktur baru
2. Halaman "Paket Saya" dengan step by step view
3. Integrasi Materi dan Tryout dalam Package view
4. Progress tracking untuk paket

### Phase 5: Tryout Standalone (2 hari)
1. Controller & Routes untuk Tryout standalone
2. View list tryout yang bisa diakses user
3. Integrasi dengan sistem akses existing

### Phase 6: Testing & Polish (2-3 hari)
1. Testing flow lengkap
2. Bug fix
3. UI/UX polish

---

## 7. Key Features Detail

### 7.1 Progress Tracking
```php
// Logic untuk tracking progress
class MaterialProgressService
{
    /**
     * Update progress untuk video
     */
    public function updateVideoProgress($userId, $materialId, $secondsWatched, $totalDuration)
    {
        $percentage = min(100, round(($secondsWatched / $totalDuration) * 100));
        
        UserMaterialAccess::updateOrCreate(
            ['user_id' => $userId, 'material_id' => $materialId],
            [
                'progress_percentage' => $percentage,
                'status' => $percentage >= 90 ? 'completed' : 'in_progress',
                'completed_at' => $percentage >= 90 ? now() : null,
            ]
        );
        
        // Log progress
        MaterialProgressLog::create([
            'user_id' => $userId,
            'material_id' => $materialId,
            'event_type' => 'viewed',
            'progress_seconds' => $secondsWatched,
        ]);
    }
    
    /**
     * Mark dokumen sebagai selesai dibaca
     */
    public function completeDocument($userId, $materialId)
    {
        UserMaterialAccess::updateOrCreate(
            ['user_id' => $userId, 'material_id' => $materialId],
            [
                'progress_percentage' => 100,
                'status' => 'completed',
                'completed_at' => now(),
            ]
        );
    }
}
```

### 7.2 Unlock Condition (Untuk Paket)
```php
// Contoh unlock condition
{
    "type": "sequential",  // atau "prerequisite", "timed"
    "prerequisites": [
        {"type": "material", "id": 1, "status": "completed"},
        {"type": "tryout", "id": 5, "status": "completed", "min_score": 70}
    ],
    "available_at": null,  // atau datetime
    "delay_hours": 24      // Delay setelah prerequisite selesai
}
```

### 7.3 Access Check Logic
```php
class MaterialAccessService
{
    /**
     * Cek apakah user bisa akses material
     */
    public function canAccess($userId, $materialId): bool
    {
        // 1. Cek akses langsung (purchased/subscription)
        $directAccess = UserMaterialAccess::where('user_id', $userId)
            ->where('material_id', $materialId)
            ->whereIn('access_type', ['purchased', 'subscription'])
            ->exists();
            
        if ($directAccess) return true;
        
        // 2. Cek akses dari package
        $material = Material::find($materialId);
        $packageIds = $material->packages()->pluck('packages.package_id');
        
        return UserPackageAcces::where('user_id', $userId)
            ->whereIn('package_id', $packageIds)
            ->where('status', 'active')
            ->where(function ($q) {
                $q->whereNull('end_date')->orWhere('end_date', '>', now());
            })
            ->exists();
    }
}
```

---

## 8. API Endpoints (Ajax)

```php
// Progress tracking via AJAX
Route::prefix('api/material')->middleware('auth')->group(function () {
    Route::post('/{material}/progress', [MaterialApiController::class, 'updateProgress']);
    Route::get('/{material}/progress', [MaterialApiController::class, 'getProgress']);
    Route::post('/{material}/complete', [MaterialApiController::class, 'markComplete']);
});
```

---

## 9. Migration Checklist

```bash
# 1. Buat migration
php artisan make:migration create_materials_table
php artisan make:migration create_material_categories_table
php artisan make:migration create_material_category_pivot_table
php artisan make:migration create_user_material_access_table
php artisan make:migration create_material_progress_logs_table
php artisan make:migration create_package_materials_table

# 2. Run migration
php artisan migrate

# 3. Seeder (opsional)
php artisan make:seeder MaterialCategorySeeder
php artisan make:seeder MaterialSeeder
```

---

## 10. Catatan Penting

1. **Backward Compatibility**: Routes dan fitur existing harus tetap berfungsi selama transisi
2. **Data Migration**: Siapkan script untuk migrasi data existing jika diperlukan
3. **Permission**: Pastikan permission system tetap berfungsi untuk admin
4. **Performance**: Index untuk kolom yang sering di-query (user_id, material_id, status)
5. **File Storage**: Pertimbangkan storage untuk video (streaming) dan PDF
6. **SEO**: Untuk materi yang public, pertimbangkan meta tags

---

## 11. Alternatif Simplifikasi (Jika Waktu Terbatas)

Jika ingin versi yang lebih sederhana:

1. **Skip** `material_progress_logs` - Cukup simpan progress di `user_material_access`
2. **Skip** `package_materials` - Gunakan `detail_packages` polymorphic saja
3. **Skip** kategori - Materi langsung difilter by type
4. **Skip** unlock condition - Semua materi dalam paket langsung terbuka
5. **Sederhanakan** sidebar - Tidak perlu dropdown, menu flat saja

Dengan simplifikasi ini, waktu development bisa dipersingkat 30-40%.
