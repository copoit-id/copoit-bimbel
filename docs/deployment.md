# Deployment Production

## Dependensi dan cache

Setiap deployment harus memasang dependensi berdasarkan `composer.lock` sebelum menjalankan perintah Artisan. Ini wajib terutama setelah perubahan yang menambahkan package runtime, seperti Laravel Sanctum.

```bash
git pull
composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction
composer run sanctum:check --no-interaction
php artisan migrate --force
php artisan optimize
```

Baris verifikasi akan gagal (exit code `1`) bila trait Sanctum belum tersedia. Jangan lanjutkan deployment atau restart PHP-FPM sebelum perintah tersebut sukses.

Setelah perubahan dependency, restart proses PHP yang berjalan agar tidak memakai autoloader lama:

```bash
sudo systemctl reload php8.3-fpm
```

Gunakan versi service PHP-FPM yang sesuai pada server bila namanya berbeda.
