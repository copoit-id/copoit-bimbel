# AGENTS.md - BIMBELHUB

Dokumen ini adalah panduan utama untuk setiap agent/developer yang bekerja di repository ini.

## Wajib sebelum mengubah kode

1. Baca file ini sampai selesai.
2. Baca [docs/CODING_RULES.md](docs/CODING_RULES.md).
3. Baca dokumen detail yang relevan di `docs/coding/`.
4. Periksa `git status` dan pertahankan perubahan user yang tidak terkait.
5. Setelah perubahan, jalankan validasi yang sesuai dan laporkan hasilnya.

## Gambaran proyek

- Framework: Laravel 11
- PHP: 8.2+
- Database: MySQL
- Frontend: Blade, Tailwind CSS, Alpine.js
- Server production: Linux Ubuntu, filesystem case-sensitive
- Arsitektur: controller tipis, service untuk business logic, Eloquent untuk akses data

## Prinsip wajib

- Utamakan keamanan, correctness, maintainability, performa, dan skalabilitas.
- Jangan membuat perubahan besar di luar permintaan user.
- Jangan menghapus atau mereset data/kode secara destruktif tanpa persetujuan jelas.
- Gunakan route model binding, Form Request/validasi terpusat, dependency injection, dan return type.
- Hindari N+1, query dalam loop, `SELECT *` yang tidak perlu, dan pengambilan data tanpa batas.
- Semua migration harus aman dijalankan di production dan memiliki `down()` yang masuk akal.
- Jangan menaruh business logic kompleks di Blade atau controller.
- Sebelum membuat service baru, cari dan gunakan service existing yang sudah menangani domain tersebut.
- Buat service baru hanya jika tanggung jawabnya benar-benar berbeda; jelaskan alasan jika tidak bisa reuse.
- FE harus sederhana: tampilkan data dari BE dan kelola interaksi UI saja.
- Untuk data besar, jangan mengambil seluruh dataset sebagai collection Eloquent; gunakan aggregate, cursor/chunk, queue, atau bulk operation.
- Query, kalkulasi, status transition, permission, filtering, dan branching bisnis wajib berada di BE/service.
- Jangan mendeklarasikan array/closure/lookup kompleks di Blade atau JavaScript untuk menggantikan logic BE.
- BE harus mengirim view data yang sudah siap dipakai FE (view model/DTO/array terstruktur).
- Asumsikan production case-sensitive walaupun local macOS tidak selalu memperlihatkannya.

## Alur kerja perubahan

1. Pahami requirement dan area dampak.
2. Cari implementasi yang sudah ada dengan `rg`/`rg --files`, terutama service, scope, policy, query, dan component.
3. Baca aturan detail sesuai area pekerjaan.
4. Buat perubahan sekecil mungkin dengan pola yang sudah dipakai proyek.
5. Pastikan tidak menduplikasi service/business logic dan periksa authorization, validasi, query, business logic di BE, serta state FE.
6. Jalankan test/lint/cache build yang relevan.
7. Tinjau `git diff`, `git diff --check`, lalu jelaskan file dan validasi yang dilakukan.

## Peta dokumentasi detail

- [Coding rules index](docs/CODING_RULES.md)
- [Architecture and naming](docs/coding/01-architecture-and-naming.md)
- [Laravel backend](docs/coding/02-laravel-backend.md)
- [Database and performance](docs/coding/03-database-performance.md)
- [Blade and frontend](docs/coding/04-frontend-blade.md)
- [Security, testing, and deployment](docs/coding/05-security-testing-deployment.md)

## Perintah umum

```bash
php artisan serve
npm run dev
php artisan test
php artisan view:cache
npm run build
```

Production deploy: `composer install --no-dev --optimize-autoloader`, migrate with `--force`, then cache config/routes/views and run `php artisan optimize`.

## Catatan penting

- Komponen Blade harus lowercase/kebab-case: `<x-ui.button>`.
- Jangan memakai `$guarded = []`.
- List besar wajib pagination/chunk/lazy collection.
- Jangan menambahkan eager load global di model tanpa alasan kuat.
- Cache hanya untuk data yang tepat dan harus diinvalidasi saat sumber berubah.
