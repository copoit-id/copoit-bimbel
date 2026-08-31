# Coding Rules - BIMBELHUB

File ini adalah index aturan teknis. `AGENTS.md` berisi alur kerja umum; dokumen di `docs/coding/` berisi detail yang wajib diikuti sesuai area perubahan.

| Area | Dokumen |
|---|---|
| Struktur folder, naming, arsitektur | [01-architecture-and-naming.md](coding/01-architecture-and-naming.md) |
| Controller, model, service, route | [02-laravel-backend.md](coding/02-laravel-backend.md) |
| Query, migration, index, cache | [03-database-performance.md](coding/03-database-performance.md) |
| Blade, component, Tailwind, Alpine | [04-frontend-blade.md](coding/04-frontend-blade.md) |
| Security, testing, deployment, Git | [05-security-testing-deployment.md](coding/05-security-testing-deployment.md) |

## Kontrak minimum

- Nama file/folder lowercase atau kebab-case yang aman di Linux.
- Authorization ditegakkan di middleware/policy/service, bukan hanya menyembunyikan tombol.
- Input divalidasi dan output user di-escape.
- Query tidak N+1 dan list tidak mengambil data tanpa batas.
- Operasi multi-tabel memakai transaction.
- Migration production-aware, idempotent bila perlu, dan punya rollback.
- UI memiliki loading, empty, validation, error, dan success state bila relevan.
- Perubahan diuji dan diff diperiksa sebelum diserahkan.

Jika aturan tampak bertentangan, pilih pendekatan yang paling aman dan konsisten dengan arsitektur existing, lalu dokumentasikan keputusan.
