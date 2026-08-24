# Plan: Voucher Scope (Paket & Jenis Pembelian)

## Tujuan
Membuat voucher bisa dibatasi scope-nya:
1. **Paket**: berlaku untuk semua paket atau paket tertentu (checklist).
2. **Jenis pembelian**: berlaku untuk pembelian paket, tryout, materi, dan/atau tes koran (checklist).

## Temuan
- Model voucher = `App\Models\Discount` dengan `application_type = 'voucher'`.
- Form admin ada di `resources/views/admin/pages/discounts/_form.blade.php`.
- Validasi & CRUD di `app/Http/Controllers/admin/DiscountController.php`.
- Penerapan kode voucher untuk pembelian paket di `app/Http/Controllers/user/PackageController.php::resolveDiscount()`.
- Pembelian individual (tryout/materi/tes koran) ada di `app/Http/Controllers/IndividualPurchaseController.php`, saat ini belum mendukung kode voucher.

## Pendekatan

### 1. Struktur Data
Tambahkan 2 kolom JSON pada tabel `discounts`:
- `applicable_package_ids` (nullable) — array `package_id`, `null` = semua paket.
- `applicable_purchase_types` (array) — contoh `['package']`, `['tryout','material']`, dsb.

Migration juga akan melakukan backfill record voucher lama:
- `applicable_purchase_types` = `['package']`
- `applicable_package_ids` = `null` (semua paket)

### 2. Model (`App\Models\Discount`)
- Cast kedua kolom baru sebagai `array`.
- Tambah helper:
  - `appliesToPackage(int $packageId): bool`
  - `appliesToPurchaseType(string $type): bool`
- Perluas `validationErrorFor()` dengan parameter opsional `?int $packageId` dan `?string $purchaseType`.

### 3. Admin Form & Controller
- `_form.blade.php`: tambah section checklist:
  - "Semua paket" toggle + daftar checkbox paket aktif.
  - Checkbox jenis pembelian: Paket, Tryout, Materi, Tes Koran.
- `DiscountController::validatedData()`: validasi array kolom baru.
- `DiscountController::normalizeValidatedData()`: normalisasi `null` untuk semua paket, filter purchase types.
- `index.blade.php`: tampilkan badge scope paket & jenis pembelian.

### 4. Pembelian Paket (`PackageController`)
- `resolveDiscount()`: setelah menemukan voucher, panggil `validationErrorFor($amount, $userId, $package->package_id, 'package')`.
- `automaticDiscountForPackage()` / `automaticDiscountsForPackages()`: filter hanya diskon otomatis (tidak voucher) yang relevan.
- `index()` & `detail()`: filter `publicDiscounts` agar hanya menampilkan voucher yang berlaku untuk paket saat ini dan purchase type `package`.

### 5. Pembelian Individual (Tryout/Materi/Tes Koran)
Jika scope mencakup jenis individual, perlu:
- Tambah kolom `discount_id`, `discount_code`, `discount_amount` di tabel `individual_purchases`.
- Update model `IndividualPurchase` fillable & casts.
- Update `IndividualPurchaseController::buy()` & `gatewayRedirect()` untuk menerima `discount_code` opsional, validasi scope & hitung total.
- Update view form pembelian individual (tryout list, materi, tes koran) menambahkan input kode voucher + preview sederhana.

## Opsi Implementasi

### Opsi A — Scope voucher + penerapan di paket saja (Recommended)
- Implementasi poin 1–4 saja.
- Checklist jenis pembelian tetap ada di form, tetapi voucher untuk individual belum bisa dipakai di UI pembelian individual (hanya disimpan sebagai konfigurasi).
- Lebih cepat, lebih aman, tidak mengubah flow individual purchase.

### Opsi B — Lengkap termasuk individual purchase
- Implementasi poin 1–5.
- Voucher benar-benar bisa dipakai saat membeli tryout/materi/tes koran.
- Lebih banyak file berubah (migration, controller, model, view individual purchase).

## File yang akan diubah (Opsi A)
- `database/migrations/2026_06_12_xxxx_add_voucher_scope_to_discounts_table.php`
- `app/Models/Discount.php`
- `app/Http/Controllers/admin/DiscountController.php`
- `resources/views/admin/pages/discounts/_form.blade.php`
- `resources/views/admin/pages/discounts/index.blade.php`
- `app/Http/Controllers/user/PackageController.php`

## File tambahan jika Opsi B
- `database/migrations/2026_06_12_xxxx_add_discount_to_individual_purchases_table.php`
- `app/Models/IndividualPurchase.php`
- `app/Http/Controllers/IndividualPurchaseController.php`
- `resources/views/user/pages/tryout/new-list.blade.php`
- `resources/views/user/pages/material/*.blade.php`
- `resources/views/user/pages/tes-koran/index.blade.php`

## Risiko & Mitigasi
- **Data lama**: migration backfill memastikan voucher lama tetap berlaku untuk semua paket dan pembelian paket.
- **Performance**: filter JSON array dilakukan di aplikasi atau dengan `JSON_CONTAINS` jika perlu; untuk jumlah paket/voucher yang tidak besar, filter koleksi cukup.
- **Case sensitive file/folder**: semua file view sudah lowercase sesuai AGENTS.md.
