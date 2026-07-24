# Chat Siswa–Tutor

Fitur ini menggunakan Laravel Reverb dengan private channel. Pesan selalu disimpan ke database terlebih dahulu; event realtime baru dikirim setelah transaksi berhasil di-commit.

## Arsitektur

- Satu percakapan adalah satu konteks `kelas + siswa + tutor`.
- Unique index pada konteks tersebut mencegah dua thread tercipta ketika siswa membuka chat dari beberapa perangkat sekaligus.
- Setiap request kirim pesan wajib membawa `client_message_id` UUID. Retry dari browser/mobile akan mengembalikan pesan lama, bukan menduplikasinya.
- Pengiriman dalam satu percakapan memakai row lock. `last_message_id` dan urutan pesan selalu konsisten ketika kedua pihak mengirim pada waktu bersamaan.
- Channel `private-chat.conversation.{ulid}` hanya dapat diautentikasi oleh siswa yang masih memiliki akses kelas atau tutor aktif yang memang ditugaskan pada thread itu.

## Konfigurasi produksi

Tambahkan nilai Reverb nyata pada `.env` (jangan memakai contoh kosong):

```dotenv
BROADCAST_CONNECTION=reverb
QUEUE_CONNECTION=database

REVERB_APP_ID=...
REVERB_APP_KEY=...
REVERB_APP_SECRET=...
REVERB_HOST=chat.domain-anda.com
REVERB_PORT=443
REVERB_SCHEME=https
REVERB_SERVER_HOST=0.0.0.0
REVERB_SERVER_PORT=8080
REVERB_ALLOWED_ORIGINS=https://domain-anda.com

VITE_REVERB_APP_KEY="${REVERB_APP_KEY}"
VITE_REVERB_HOST="${REVERB_HOST}"
VITE_REVERB_PORT="${REVERB_PORT}"
VITE_REVERB_SCHEME="${REVERB_SCHEME}"
```

Jalankan proses berikut secara permanen melalui Supervisor/systemd:

```bash
php artisan queue:work --queue=broadcasts,default --tries=3 --timeout=90
php artisan reverb:start
```

Nginx harus mem-proxy WebSocket ke port Reverb dan meneruskan header `Upgrade` serta `Connection`. Untuk beberapa instance aplikasi, aktifkan `REVERB_SCALING_ENABLED=true` dan gunakan Redis yang sama pada setiap instance.

Sesudah mengubah environment atau source:

```bash
composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction
composer run sanctum:check --no-interaction
npm ci && npm run build
php artisan migrate --force
php artisan optimize
php artisan reverb:restart
```

Jangan melewati `composer install`: trait `HasApiTokens` yang dipakai model `User` berasal dari package Sanctum. Perintah verifikasi harus sukses sebelum aplikasi atau PHP-FPM direstart.

## API untuk mobile

Seluruh endpoint memakai Bearer token Sanctum dan prefix `/api/chat`.

| Method | Endpoint | Fungsi |
| --- | --- | --- |
| `GET` | `/conversations` | Daftar thread milik pengguna aktif |
| `POST` | `/conversations` | Buka thread siswa, body: `class_id` |
| `GET` | `/conversations/{id}/messages` | Riwayat cursor dengan `before_id` dan `limit` |
| `POST` | `/conversations/{id}/messages` | Kirim `body` dan `client_message_id` UUID |
| `POST` | `/conversations/{id}/read` | Tandai terbaca, opsional `last_message_id` |

Token dibuat melalui `$user->createToken('nama-perangkat', ['chat:read', 'chat:write'])`. Aplikasi mobile sebaiknya memakai UUID baru untuk setiap pesan lokal dan menyimpan UUID itu sampai respons berhasil diterima.
