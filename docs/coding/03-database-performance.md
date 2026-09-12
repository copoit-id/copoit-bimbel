# Database and performance

## Query

- Hindari N+1 dengan eager loading terukur; sertakan key yang diperlukan saat memakai `select`.
- Ambil kolom yang dipakai saja.
- List gunakan `paginate`, `simplePaginate`, atau `cursorPaginate`.
- Data besar jangan memakai `Model::all()`, `get()` tanpa batas, atau collection Eloquent penuh.
- Untuk data besar prioritaskan Query Builder dengan `select` minimal, aggregate database, `chunkById`, `lazyById`, cursor pagination, bulk update, atau queue.
- Eloquent tetap boleh untuk record terbatas dan relasi yang diperlukan; jangan hydrate jutaan model.
- Jangan query di dalam loop; gunakan kumpulan ID dan satu query.
- Gunakan `exists()`/`count()` bila tidak membutuhkan model lengkap.
- Parameter user tidak boleh langsung masuk ke SQL/raw ordering.

## Large data strategy

| Kebutuhan | Pendekatan |
|---|---|
| Detail beberapa record | Eloquent dengan `select` dan eager load terukur |
| List user-facing | `paginate`/`cursorPaginate` dengan filter terindeks |
| Rekap/count/sum | Query Builder dan aggregate SQL |
| Proses semua baris | `chunkById`/`lazyById` tanpa hydration berlebihan |
| Import/export besar | Queue, batch, stream, dan progress tracking |
| Update massal | Bulk query terparameterisasi bila aturan memungkinkan |

Jangan menjalankan query berulang dalam loop. Gunakan `GROUP BY`, subquery, aggregate, atau satu query terukur.

## N+1 prevention

- Review semua akses relasi di loop Blade/controller.
- Gunakan eager load terukur dan pilih kolom yang diperlukan.
- Untuk count/average gunakan `withCount`/`withAvg` atau aggregate query.
- Buktikan query count dengan query log/test pada halaman penting.
- Jangan memakai eager load global sebagai solusi cepat.

## Index and cache

- Index kolom untuk filter, join, sort, unique lookup, dan foreign key.
- Composite index harus mengikuti urutan filter query nyata.
- Cache data yang stabil dengan key dan TTL jelas.
- Invalidate cache setelah mutation; jangan mengandalkan TTL untuk data kritis.

## Migrations

- Cek `hasTable()`/`hasColumn()` bila perlu untuk variasi schema.
- Hindari operasi berat/locking besar pada production tanpa rencana.
- Pertimbangkan data existing saat foreign key, unique index, rename, atau drop.
- `down()` membalikkan perubahan dengan pengecekan aman.
- Data migration besar sebaiknya dipisah atau diproses bertahap.
- Jangan menjalankan `migrate:fresh` pada production.

## Checklist

- Cek query count/N+1, pagination, payload, index, memory export/import, dan kebutuhan queue.
