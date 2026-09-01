# Security, testing, deployment, and Git

## Security

- Validasi dan authorize setiap mutation di server.
- Gunakan Eloquent/query builder untuk parameter binding.
- Jangan concat input ke SQL, shell command, path, atau redirect.
- Escape output untuk mencegah XSS.
- Jangan log password, token, secret, atau data pribadi berlebihan.
- Gunakan rate limit untuk endpoint sensitif/public.
- File upload wajib membatasi ukuran, MIME/type, nama/path, dan lokasi storage.

## Threat checklist

- SQL injection: parameter binding dan whitelist kolom untuk sort/filter dinamis.
- XSS: output escaped; rich text disanitasi dan tidak menerima script/event handler.
- CSRF: semua mutation web memakai CSRF; API memakai autentikasi yang tepat.
- IDOR/broken access control: mengganti ID/URL tidak boleh membuka record user lain.
- Authentication/session: password di-hash, session diregenerasi saat login, logout menginvalidasi session, endpoint sensitif dilimit.
- Mass assignment: `$fillable` whitelist; jangan menerima ownership/status/role dari request tanpa aturan.
- File upload: validasi MIME/ukuran di server, nama acak, lokasi tidak executable, dan cegah path traversal.
- SSRF/open redirect: URL tujuan di-allowlist/validasi; jangan fetch URL user secara bebas.
- Sensitive data: jangan expose secret, token, data user lain, atau detail internal di JSON/log.
- Race condition: transaction, lock, unique constraint, dan cek ulang state untuk quota, saldo, booking, dan status.
- Abuse: throttle, pagination limit, payload limit, dan timeout untuk endpoint mahal.

Security tidak boleh bergantung pada FE. Request manual lewat curl/Postman harus tetap aman.

## Testing

- Test authorization, validation, boundary value, database mutation, relasi, dan workflow service.
- Cek N+1/query count untuk query penting dan uji dataset realistis.
- Uji data besar dengan memory/time limit, pagination, chunking, dan queue yang sesuai.
- Jalankan test/lint/build yang terkait sebelum handoff.

## Deployment

```bash
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize
```

Backup dan rencanakan rollback migration. Jangan commit `.env`. Setelah deploy, cek queue, scheduler, storage permission, cache, log, dan endpoint kritis.

## Git

Gunakan commit singkat dan jelas, misalnya `feat: add tutor booking approval`, `fix: prevent n+1 on dashboard`, atau `docs: update coding rules`. Sebelum handoff jalankan `git status` dan `git diff --check`.
