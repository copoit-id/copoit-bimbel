# Laravel backend rules

## Controllers

- Wajib return type (`: View`, `: RedirectResponse`, `: JsonResponse`).
- Gunakan dependency injection dan route model binding.
- Validasi input sebelum business logic; gunakan Form Request jika kompleks/reusable.
- Gunakan `DB::transaction()` untuk perubahan beberapa tabel.
- Setelah mutation, redirect dengan flash success/error atau response API konsisten.
- Tangkap exception hanya bila bisa recovery; `report($e)` dan jangan bocorkan detail internal.

## Services and models

- Workflow bisnis berada di service/action yang dapat diuji.
- Sebelum membuat service, cari service existing berdasarkan domain dan method dengan `rg app/Services app/Http/Controllers`.
- Reuse service existing jika tanggung jawabnya sama; jangan membuat beberapa service untuk domain yang sama.
- Service baru harus punya tanggung jawab tunggal, dependency injection, dan alasan mengapa service existing tidak sesuai.
- Jika service existing diperluas, pertahankan behavior lama dan jangan copy-paste logic.
- Model memakai `$fillable` minimal; jangan gunakan `$guarded = []`.
- Definisikan return type relasi (`BelongsTo`, `HasMany`, dst.).
- Gunakan `$casts` untuk boolean, angka, tanggal, dan JSON.
- Gunakan scope untuk filter berulang; hindari `$with` global tanpa alasan kuat.

## Business logic ownership

- Semua rule bisnis harus dapat dijalankan dan diuji tanpa browser.
- Controller mengorkestrasi; service/action menghitung, memvalidasi workflow, dan mengubah data.
- Kirim ke FE nilai final, label, permission flag, dan collection yang sudah difilter/diurutkan.
- Jangan mengandalkan hidden input, disabled button, atau kondisi Blade sebagai security boundary.
- Ulangi validasi dan authorization saat mutation karena request dapat dibuat manual.

## Service reuse checklist

1. Apakah ada service existing untuk domain ini?
2. Apakah ada method, scope, policy, atau query object yang bisa dipakai?
3. Apakah logic ini seharusnya menjadi method cohesive di service existing?
4. Apakah caller lama sudah diuji setelah service diperluas?

## Routes and authorization

- Kelompokkan route berdasarkan prefix, name, middleware, dan portal.
- Authorization wajib ditegakkan di server.
- Route custom yang berpotensi bentrok ditempatkan sebelum wildcard.
- Jangan mengubah route name existing tanpa mengecek semua pemakai.

## API/AJAX

- Gunakan status HTTP yang tepat dan JSON shape konsisten.
- Bedakan error 401/403/404/422/500.
- Jangan menyamarkan semua error menjadi satu pesan generik saat debugging.
