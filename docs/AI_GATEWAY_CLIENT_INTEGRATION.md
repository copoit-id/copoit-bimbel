# Panduan Integrasi AI Gateway untuk Project Non-Gateway

Dokumen ini untuk developer project seperti **CPNS Academy** yang memakai AI Gateway pusat dari BimbelHub. CPNS Academy adalah **murni project penerima (non-gateway)**: tidak ada panel gateway, tidak ada tabel subscription gateway pusat, tidak ada API key OpenAI/Gemini, dan tidak ada credential payment gateway AI di project tersebut. Semua model AI, kuota, paket AI, invoice AI, webhook provider AI, pembayaran AI, serta perhitungan biaya/laba berada di BimbelHub.

Gunakan dokumen ini sebagai kontrak integrasi. Untuk contoh implementasi Laravel yang lebih lengkap, lihat file referensi di project BimbelHub pada bagian akhir dokumen.

## 1. Gambaran arsitektur

```text
Browser user
    │ request ke route aplikasi CPNS Academy yang sudah login
    ▼
CPNS Academy (server)
    │ X-AI-Gateway-Key, server-to-server
    ▼
BimbelHub AI Gateway pusat
    ├─ validasi project, user, paket/trial, dan kuota
    ├─ memanggil OpenAI atau Gemini
    ├─ mencatat token dan biaya
    └─ membuat/memverifikasi pembayaran AI melalui gateway pusat
```

**Aturan paling penting:** `AI_GATEWAY_KEY` hanya boleh berada di server CPNS Academy. Jangan pernah dikirim ke Blade, JavaScript, aplikasi mobile, response API untuk user, atau log yang dapat dibaca user.

### Pembagian tanggung jawab yang wajib

| Bagian | CPNS Academy (non-gateway) | BimbelHub (gateway pusat) |
|---|---|---|
| User, tryout, soal, jawaban, dan akses produk CPNS | Sumber kebenaran | Tidak menyimpan data soal CPNS |
| Konteks pembahasan yang dikirim ke AI | Mengambil dari DB sendiri setelah validasi akses | Memproses konteks yang diterima |
| Provider/model AI dan API key AI | Tidak ada | Diatur super admin BimbelHub |
| Kuota token/chat dan trial AI | Hanya membaca status | Sumber kebenaran dan pencatatan |
| Paket AI, harga, invoice AI, payment provider AI, webhook AI | Hanya menampilkan/mengarahkan user | Seluruhnya dikelola di gateway |
| Pembayaran produk tryout CPNS | Tetap mengikuti sistem pembayaran CPNS sendiri | Tidak ikut memverifikasi pembayaran produk CPNS |

Pembayaran **tryout** dan **paket AI** adalah dua status yang berbeda. UI boleh menawarkan keduanya dalam satu proses pembelian (*combined checkout*), tetapi jangan menyatukan atau menyamakan statusnya secara teknis.

## 2. Persiapan di gateway pusat

Super admin BimbelHub harus melakukan ini terlebih dahulu:

1. Buat **AI Gateway Client** untuk CPNS Academy.
2. Isi `base_url` dengan origin production CPNS Academy, misalnya `https://cpnsacademy.com`.
3. Atur trial gratis bila diperlukan (`free_token_limit` dan/atau `free_chat_limit`).
4. Buat dan aktifkan paket AI: harga, batas token, limit chat (`0` untuk unlimited), dan masa aktif.
5. Pastikan provider/model AI dan payment gateway AI pusat sudah aktif.
6. Salin API key client saat key dibuat. Key hanya ditampilkan sekali.

`base_url` harus tepat karena gateway hanya menerima URL redirect pembayaran dari origin yang terdaftar.

## 3. Konfigurasi project CPNS Academy

Tambahkan konfigurasi server-side berikut. Jangan commit key ke repository.

```env
AI_GATEWAY_URL=https://demo.bimbelhub.com/api/ai-gateway/discussion
AI_GATEWAY_KEY=aigw_xxxxxxxxxxxxxxxxxxxxxxxxx
```

Tambahkan ke `config/services.php`:

```php
'ai_gateway' => [
    'url' => env('AI_GATEWAY_URL'),
    'key' => env('AI_GATEWAY_KEY'),
],
```

Setelah mengubah `.env` di production, jalankan `php artisan config:cache`.

## 4. Service HTTP gateway di CPNS Academy

Buat satu service, misalnya `app/Services/AiGatewayClientService.php`. Semua controller harus memakai service ini; jangan menyalin request HTTP ke banyak tempat.

Tanggung jawab service:

- membentuk base URL dari `AI_GATEWAY_URL`;
- menambahkan header `X-AI-Gateway-Key`;
- memakai `acceptJson()` dan timeout;
- mengubah error gateway menjadi error yang aman untuk user;
- tidak pernah menulis API key ke log.

Contoh inti request:

```php
$response = Http::acceptJson()
    ->timeout(15)
    ->withHeaders([
        'X-AI-Gateway-Key' => config('services.ai_gateway.key'),
    ])
    ->post($baseUrl . '/checkout', $payload);
```

`$baseUrl` adalah URL gateway tanpa `/discussion`, sehingga dari konfigurasi di atas hasilnya `https://demo.bimbelhub.com/api/ai-gateway`.

## 5. Kontrak endpoint gateway

Semua endpoint berada di bawah `/api/ai-gateway` pada domain gateway pusat.

| Tujuan | Method dan endpoint | Dipanggil dari |
|---|---|---|
| Daftar paket AI | `GET /plans` | Server CPNS Academy |
| Status paket/trial user | `GET /subscription?external_user_id={id}` | Server CPNS Academy |
| Buat invoice pembayaran | `POST /checkout` | Server CPNS Academy |
| Chat pembahasan AI | `POST /discussion` | Server CPNS Academy |

Kecuali `/plans`, semua endpoint di atas harus membawa header:

```http
X-AI-Gateway-Key: aigw_xxx
Accept: application/json
```

### 5.1 `GET /plans`

Response berupa array paket aktif:

```json
[
  {
    "id": 1,
    "name": "Paket Hemat",
    "slug": "hemat",
    "price": 25000,
    "token_limit": 50000,
    "chat_limit": 0,
    "duration_days": 30
  }
]
```

Tampilkan harga/paket dari response gateway, bukan hardcode di CPNS Academy.

`token_limit` selalu lebih dari `0` dan menjadi batas utama penggunaan paket. `chat_limit = 0` berarti jumlah chat unlimited selama token paket masih tersedia dan masa aktif paket belum berakhir. Bila `chat_limit > 0`, batas chat tersebut tetap harus dihormati untuk kompatibilitas dengan paket lama.

### 5.2 `GET /subscription`

Query wajib:

```text
external_user_id=<ID user CPNS Academy>
```

Gunakan ID user internal CPNS Academy yang stabil dan selalu stringify, misalnya `(string) $request->user()->getAuthIdentifier()`.

Bagian response yang perlu dipakai UI:

- `subscription`: paket aktif utama, atau `null`;
- `subscriptions`: seluruh paket aktif;
- `pending_payment`: invoice pending yang masih dapat dilanjutkan;
- `trial`: batas dan pemakaian trial.

Jangan menganggap pembayaran berhasil hanya dari query parameter redirect. Setelah user kembali dari payment gateway, panggil endpoint ini lagi. User hanya boleh memakai AI bila ada trial tersisa atau `subscription.status === "active"` dan kuota belum habis.

### 5.3 `POST /checkout`

Request body:

```json
{
  "plan_id": 1,
  "external_user_id": "123",
  "customer_name": "Nama User",
  "customer_email": "user@example.com",
  "success_redirect_url": "https://cpnsacademy.com/user/paket-ai?payment=success",
  "failure_redirect_url": "https://cpnsacademy.com/user/paket-ai?payment=failed"
}
```

Response sukses:

```json
{
  "invoice_url": "https://payment-provider.example/...",
  "external_id": "AIGW-..."
}
```

Alur yang wajib:

1. Validasi user login dan `plan_id` di CPNS Academy.
2. Ambil nama dan email dari user yang sedang login; jangan menerima identitas pembeli dari browser.
3. Validasi `return_url` hanya boleh dari origin CPNS Academy.
4. Panggil `/checkout` dari server.
5. Simpan `invoice_url` sebagai pembayaran pending di session/database lokal agar tombol berubah menjadi **Lanjutkan pembayaran**, bukan **Mulai**.
6. Redirect browser ke `invoice_url`.
7. Saat kembali, panggil `/subscription` untuk sinkronisasi. Bila status belum aktif, tetap tampilkan **Lanjutkan pembayaran** atau status pending.

Gateway pusat menangani Xendit, Midtrans, iPaymu, atau InterActive QRIS. CPNS Academy **tidak membuat webhook provider dan tidak menyimpan credential provider**.

### 5.4 `POST /discussion`

Endpoint ini hanya untuk server-to-server. Request:

```json
{
  "message": "Mengapa jawaban B salah?",
  "external_user_id": "123",
  "external_user_name": "Nama User",
  "external_user_email": "user@example.com",
  "project_base_url": "https://cpnsacademy.com",
  "question_reference": "456",
  "context": {
    "tryout_name": "Tryout SKD 1",
    "subtest_name": "TWK",
    "question_type": "multiple_choice",
    "question_text": "Isi soal dari database...",
    "options": [
      {"key": "A", "text": "Pilihan A"},
      {"key": "B", "text": "Pilihan B"}
    ],
    "selected_answer": "Pilihan A",
    "explanation": "Pembahasan resmi dari database..."
  }
}
```

Response sukses mencakup:

```json
{
  "message": "Penjelasan AI...",
  "model": "gemini-2.5-flash",
  "provider": "gemini",
  "usage": {"input": 100, "output": 200, "total": 300},
  "response_time_ms": 850,
  "quota": {
    "type": "package",
    "token_limit": 50000,
    "tokens_used": 300,
    "chat_limit": 0,
    "chats_used": 1
  }
}
```

Pada object `quota`, `chat_limit = 0` berarti unlimited. UI harus menampilkan label **Chat unlimited** dan menentukan paket habis dari `tokens_used >= token_limit` (serta batas chat hanya bila `chat_limit > 0`).

Error yang harus ditangani UI:

| HTTP | Arti | Tampilan yang disarankan |
|---|---|---|
| 401 | Key gateway salah/tidak aktif | Jangan tampilkan detail teknis; catat untuk admin. |
| 403 | Tidak ada paket/trial AI | Tampilkan tombol beli paket AI. |
| 422 | Konteks/permintaan salah atau provider gagal | Tampilkan pesan aman dari gateway. |
| 429 | Kuota token/chat habis | Tampilkan tombol beli/perpanjang paket. |
| 5xx/timeout | Gateway tidak dapat dihubungi | Tampilkan tombol coba lagi. |

## 6. Combined checkout: beli tryout/paket + tambah Pembahasan AI

Bagian ini wajib dibuat bila user dapat membeli tryout atau paket CPNS sambil mencentang **Tambahkan Pembahasan AI**.

### 6.1 Prinsip utama

1. **Ada dua transaksi independen:** transaksi produk CPNS dan transaksi paket AI di gateway BimbelHub.
2. “Combined” hanya menyatukan pengalaman UI dan mengaitkan dua transaksi tersebut; bukan berarti ada satu invoice total atau satu webhook bersama.
3. Akses tryout hanya aktif dari pembayaran produk CPNS yang benar-benar terverifikasi oleh sistem pembayaran CPNS.
4. Kuota AI hanya aktif bila gateway BimbelHub mengembalikan subscription/trial yang valid.
5. Status redirect `?payment=success`, data session, atau response dari browser tidak pernah cukup untuk mengaktifkan akses.

### 6.2 State yang perlu diikat di session/database lokal

Saat user memilih produk CPNS dan add-on AI, buat record/session server-side `combined_ai_checkout` minimal berisi:

```text
user_id
product_type                 # package atau individual/tryout
product_item_id              # package_id atau tryout_id
product_transaction_id       # diisi hanya setelah transaksi produk dibuat server
ai_plan_id
ai_external_id               # dari gateway bila tersedia
invoice_url                  # invoice AI dari gateway
expires_at                   # maksimum masa berlaku invoice, misalnya 24 jam
return_url                   # sudah divalidasi same-origin
```

Jangan mengambil nilai tersebut dari form/browser saat resume pembayaran. Saat dibaca kembali, verifikasi lagi:

- session/record milik user login yang sama;
- belum expired;
- produk yang dibuka cocok dengan `product_type` dan `product_item_id`;
- `product_transaction_id` benar-benar milik user tersebut di database CPNS;
- invoice AI masih pending/aktif menurut endpoint gateway.

### 6.3 Urutan checkout yang direkomendasikan (sama seperti BimbelHub)

1. User memilih tryout/paket CPNS dan, bila mau, memilih satu paket AI dari response `/plans`.
2. Browser mengirim request ke **route CPNS Academy**, bukan ke gateway langsung.
3. Controller CPNS Academy memvalidasi user, produk, harga produk, voucher, serta `plan_id` dari daftar paket gateway yang baru diambil.
4. Jika add-on AI dipilih, controller CPNS Academy memanggil `POST /checkout` gateway secara server-to-server dan menyimpan invoice AI sebagai pending.
5. Controller CPNS Academy membuat transaksi pembayaran produk CPNS melalui mekanisme pembayaran produk yang sudah ada.
6. Setelah transaksi produk berhasil dibuat, controller mengikat `product_transaction_id` hasil server ke `combined_ai_checkout`.
7. UI menampilkan dua aksi pembayaran bila keduanya pending:
   - **Bayar Tryout/Paket** → URL pembayaran produk CPNS.
   - **Bayar Pembahasan AI** → `invoice_url` dari gateway BimbelHub.
8. User boleh membayar dalam urutan mana pun. Setelah setiap return/webhook, server mengecek ulang status produk CPNS dan `/subscription` gateway.

Tidak boleh ada kondisi yang mengubah tombol menjadi **Mulai** hanya karena salah satu invoice berhasil dibuat atau salah satu pembayaran sukses.

### 6.4 Matriks akses (wajib dipakai UI dan backend)

| Pembayaran produk CPNS | Status AI gateway | Boleh buka tryout | Boleh chat AI pada tryout itu | UI utama |
|---|---|---:|---:|---|
| Pending/belum bayar | Pending/belum bayar | Tidak | Tidak | Lanjutkan dua pembayaran |
| Aktif/lunas | Pending/belum bayar | Ya | Tidak | Mulai tryout + Lanjutkan pembayaran AI |
| Pending/belum bayar | Aktif | Tidak | Tidak | Lanjutkan pembayaran tryout; AI tidak boleh dipakai untuk tryout yang belum berakses |
| Aktif/lunas | Aktif atau trial tersedia | Ya | Ya, selama kuota ada | Mulai + Diskusi AI |
| Aktif/lunas | Kuota AI habis | Ya | Tidak | Mulai + Beli/perpanjang paket AI |

Matriks ini harus ditegakkan ulang pada endpoint backend chat, bukan hanya di tombol frontend.

### 6.5 Pencegahan celah dan duplikasi pembayaran

- Sebelum membuat checkout baru, selalu panggil `/subscription` dan cek `pending_payment`. Jika masih ada, tampilkan **Lanjutkan pembayaran** memakai invoice yang sama.
- Gateway pusat juga memakai ulang invoice AI pending untuk user dan plan yang sama dalam rentang 24 jam. Client tetap harus menjaga state lokal agar UI tidak membuat tombol palsu.
- Gunakan idempotency/lock di CPNS Academy ketika membuat transaksi produk agar double click, refresh, atau dua tab tidak menciptakan dua pembayaran produk.
- Jangan memberikan akses tryout pada callback browser. Akses hanya dibuat setelah webhook provider produk CPNS atau server-side status check menyatakan pembayaran berhasil.
- Jangan menandai AI aktif berdasarkan `invoice_url`, `external_id`, atau `payment=success`. Hanya `/subscription` dari gateway yang menentukan status AI.
- Jika invoice AI telah dibuat tetapi transaksi produk gagal dibuat, tetap tampilkan AI sebagai pending hanya untuk user yang sama atau sediakan aksi pembatalan/biarkan invoice expired. Jangan kaitkan invoice itu ke produk lain.
- Jika produk sudah lunas namun AI pending, produk tetap boleh diakses, tetapi fitur chat AI harus terkunci dan menunjukkan invoice AI pending.
- Jika AI aktif tetapi produk belum lunas, jangan izinkan endpoint chat lolos: validasi akses tryout produk tetap dijalankan sebelum request ke gateway.

### 6.6 Data yang tidak boleh dipercaya dari browser

Browser hanya boleh mengirim pilihan seperti `product_id`, `plan_id`, atau `combined_checkout=1`. Server wajib memperoleh ulang semua data lain:

- harga produk, diskon, dan jenis produk dari database CPNS;
- nama/email/external user ID dari user login;
- URL return dari allowlist same-origin;
- URL pembayaran produk dari transaksi produk CPNS;
- URL invoice AI dari response gateway;
- status produk dari database/verification provider;
- status AI dari `/subscription`.

## 7. Route dan controller yang perlu dibuat di CPNS Academy

Sesuaikan nama route dengan struktur project CPNS Academy, tetapi minimal sediakan:

```text
GET  /user/paket-ai                 daftar paket, status kuota, dan pembayaran pending
POST /user/paket-ai/checkout        membuat checkout di gateway pusat
POST /user/.../pembahasan-ai/chat   mengirim diskusi AI untuk satu soal
POST /user/tryout/{tryout}/buy       checkout produk CPNS, opsional combined AI
GET  /user/payment/{transaction}/resume  menampilkan ulang dua pembayaran pending setelah validasi kepemilikan
```

Semua route harus memakai middleware `auth` dan rate limit. Rekomendasi:

```php
Route::post('/.../pembahasan-ai/chat', ...)
    ->middleware(['auth', 'throttle:12,1']);

Route::post('/user/paket-ai/checkout', ...)
    ->middleware(['auth', 'throttle:20,1']);
```

## 8. Aturan keamanan pembahasan AI

Bagian ini wajib diimplementasikan. Jangan percaya ID, konteks soal, atau hasil pembayaran dari browser.

Sebelum menghubungi gateway, controller CPNS Academy harus:

1. Memastikan user login.
2. Memastikan tryout/soal memang tersedia untuk pembahasan.
3. Memastikan user punya akses ke paket/tryout tersebut.
4. Memastikan attempt token milik user dan attempt sudah selesai.
5. Mengambil soal, pilihan jawaban, jawaban user, dan pembahasan resmi langsung dari database CPNS Academy.
6. Memastikan `question_id` benar-benar bagian dari attempt/tryout user tersebut.
7. Mengirim hanya konteks hasil query server ke gateway.
8. Membatasi pesan user maksimal 1.200 karakter.

Jangan menerima `question_text`, `options`, `correct_answer`, `explanation`, `external_user_id`, maupun `customer_email` dari browser sebagai sumber data tepercaya.

Gateway pusat sudah membatasi AI agar tetap membahas soal aktif, tetapi CPNS Academy tetap wajib memastikan konteks yang dikirim benar dan user berhak mengaksesnya.

## 9. Perilaku UI yang harus sama

### Halaman pembahasan

- Tampilkan chat AI hanya jika pembahasan tryout aktif dan user lolos validasi akses.
- Tampilkan paket/trial dan sisa kuota dari `/subscription`.
- Jika pembayaran masih pending, tombol harus berbunyi **Lanjutkan pembayaran** dengan `invoice_url` yang sama.
- Jangan pernah mengubah kartu menjadi **Mulai** hanya karena checkout berhasil dibuat. Status harus aktif dari gateway terlebih dahulu.
- Setelah chat sukses, tampilkan jawaban dan simpan riwayat lokal untuk percakapan pada soal yang sama.

### Modal beli tryout/paket dengan add-on AI

- Tampilkan checkbox **Tambahkan Pembahasan AI** hanya bila `/plans` mengembalikan paket aktif.
- Saat checkbox aktif, tampilkan pilihan paket AI dari gateway, bukan pilihan yang di-hardcode.
- Saat submit, nonaktifkan tombol sampai kedua request server selesai agar tidak terjadi invoice dobel.
- Bila salah satu checkout gagal, tampilkan error dan jangan menampilkan akses aktif apa pun.
- Bila dua pembayaran berhasil dibuat, tampilkan halaman/modal ringkasan dengan dua link pembayaran dan label status masing-masing.
- Saat halaman di-refresh, status pending harus dipulihkan dari server (`combined_ai_checkout` yang tervalidasi + `/subscription`), bukan hanya state JavaScript.

### Halaman paket AI

- Ambil paket dari `/plans`.
- Ambil status dari `/subscription`.
- Saat checkout berhasil, redirect ke `invoice_url`.
- Saat kembali dari provider, refresh status dari gateway sebelum merender tombol.

## 10. Penyimpanan lokal yang disarankan

CPNS Academy tidak perlu menyalin tabel subscription/transaction gateway pusat. Simpan data lokal hanya untuk kebutuhan UI dan audit, misalnya tabel log diskusi:

```text
user_id
tryout_id / exam_id
question_id
attempt_token
provider
model
input_tokens
output_tokens
total_tokens
response_time_ms
user_message
assistant_message
timestamps
```

Gunakan log ini untuk riwayat chat per soal. Jangan gunakan log lokal sebagai sumber keputusan kuota; gateway pusat adalah sumber kebenaran kuota dan status pembayaran.

## 11. Checklist pengujian sebelum rilis

- [ ] Key gateway tidak muncul di HTML, JavaScript, response user, atau repository.
- [ ] User A tidak bisa chat memakai `question_id` atau `attempt_token` milik user B.
- [ ] User tidak bisa mengirim konteks soal buatan dari browser.
- [ ] User tanpa trial/paket mendapat 403 dan tombol beli paket.
- [ ] User dengan kuota habis mendapat 429 dan tombol beli/perpanjang.
- [ ] Checkout kedua sebelum 24 jam memakai invoice pending yang sama.
- [ ] Setelah redirect sukses, tombol belum menjadi **Mulai** sebelum `/subscription` menyatakan paket aktif.
- [ ] Redirect checkout ditolak bila bukan origin CPNS Academy.
- [ ] Chat dibatasi rate limit dan pesan maksimal 1.200 karakter.
- [ ] Jika gateway timeout, UI gagal dengan aman dan user dapat mencoba lagi.
- [ ] Double click/dua tab tidak menghasilkan dua transaksi produk atau dua invoice AI yang terlihat aktif.
- [ ] Produk lunas tetapi AI pending: tryout terbuka, chat AI tetap terkunci.
- [ ] AI aktif tetapi produk pending: endpoint chat tetap menolak karena user belum memiliki akses tryout.
- [ ] Refresh/return dari pembayaran tidak pernah membuat tombol **Mulai** sebelum status produk dan AI masing-masing tervalidasi.
- [ ] Resume pembayaran menolak transaction ID, tryout ID, atau invoice milik user lain.

## 12. File referensi di project BimbelHub

Gunakan sebagai acuan, lalu sesuaikan model database/rute CPNS Academy:

| Kebutuhan | Referensi BimbelHub |
|---|---|
| Kontrak chat gateway dan kuota | `app/Http/Controllers/Api/AiGatewayController.php` |
| Paket, status, checkout, dan validasi redirect | `app/Http/Controllers/Api/AiGatewayBillingController.php` |
| Client checkout halaman paket | `app/Http/Controllers/user/AiGatewaySubscriptionController.php` |
| Client chat + validasi akses soal | `app/Http/Controllers/user/PackageController.php` (`chatPembahasanAi`) |
| Combined checkout tryout/paket + AI | `app/Http/Controllers/user/PackageController.php` (`rememberCombinedAiCheckout`, `activeCombinedAiPayment`, `resumeCombinedPayment`) |
| UI combined checkout | `resources/views/user/pages/package/new-index.blade.php` dan `resources/views/user/pages/tryout/new-list.blade.php` |
| Service request dari project non-gateway | `app/Services/AiDiscussionService.php` (`chatViaGateway`) |
| Konfigurasi | `config/services.php` (`ai_gateway`) |
| Endpoint API pusat | `routes/api.php` |

Jangan menyalin controller secara mentah. Yang harus sama adalah kontrak gateway, validasi akses, keamanan key, dan alur pembayaran; nama tabel, model, serta route harus mengikuti struktur CPNS Academy.
