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
4. Buat dan aktifkan paket AI: harga (`0` untuk paket gratis), batas token, limit chat (`0` untuk unlimited), dan masa aktif (`0` untuk tanpa masa aktif).
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
    "is_free": false,
    "token_limit": 50000,
    "chat_limit": 0,
    "duration_days": 0
  }
]
```

Tampilkan harga/paket dari response gateway, bukan hardcode di CPNS Academy.

`token_limit` selalu lebih dari `0` dan menjadi batas utama penggunaan paket. `chat_limit = 0` berarti jumlah chat unlimited selama token paket masih tersedia. `duration_days = 0` berarti paket tidak memiliki tanggal kedaluwarsa; pada response subscription nilai `ends_at` akan berupa `null`. Bila `chat_limit > 0` atau `duration_days > 0`, batas tersebut tetap harus dihormati untuk kompatibilitas dengan paket lain.

`is_free = true` menandakan paket dapat diklaim peserta tanpa pembayaran. Tampilkan tombol **Klaim gratis**, bukan tombol bayar. Satu peserta hanya dapat mengklaim paket gratis yang sama satu kali pada project client yang sama.

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
- `claimed_free_plan_ids`: ID paket gratis yang pernah diklaim peserta, termasuk yang kuotanya sudah habis atau masa aktifnya berakhir.

Jangan menganggap pembayaran berhasil hanya dari query parameter redirect. Setelah user kembali dari payment gateway, panggil endpoint ini lagi. User hanya boleh memakai AI bila ada trial tersisa atau `subscription.status === "active"` dan kuota belum habis.

Pembuatan invoice atau response API provider dengan pesan umum `success` bukan bukti pembayaran. Gateway pusat hanya mengubah transaksi menjadi `paid` setelah field status transaksi provider secara spesifik menyatakan lunas, atau setelah super admin melakukan **ACC manual** secara sadar.

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

Untuk paket dengan `is_free = true`, endpoint yang sama langsung mengaktifkan subscription dan tidak membuat invoice:

```json
{
  "message": "Paket gratis berhasil diklaim dan langsung aktif.",
  "activated": true,
  "claimed": true,
  "already_claimed": false,
  "invoice_url": null,
  "external_id": "AIGW-FREE-..."
}
```

Jika peserta mengulang klaim paket gratis yang sama, response tetap HTTP 200 dengan `already_claimed = true`. Client harus me-refresh `/subscription` dan menampilkan **Sudah diklaim**. Jangan menganggap `invoice_url = null` sebagai kegagalan ketika `activated = true` atau `already_claimed = true`.

Alur yang wajib:

1. Validasi user login dan `plan_id` di CPNS Academy.
2. Ambil nama dan email dari user yang sedang login; jangan menerima identitas pembeli dari browser.
3. Validasi `return_url` hanya boleh dari origin CPNS Academy.
4. Panggil `/checkout` dari server.
5. Jika response `activated = true`, jangan redirect ke payment provider; refresh `/subscription` dan tampilkan paket aktif.
6. Untuk paket berbayar, simpan `invoice_url` sebagai pembayaran pending di session/database lokal agar tombol berubah menjadi **Lanjutkan pembayaran**, bukan **Mulai**.
7. Redirect browser ke `invoice_url` hanya untuk paket berbayar.
8. Saat kembali, panggil `/subscription` untuk sinkronisasi. Bila status belum aktif, tetap tampilkan **Lanjutkan pembayaran** atau status pending.

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
  "feature": "discussion",
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

Field `feature` opsional dan default-nya `discussion`. Untuk AI Learning Tools gunakan salah satu nilai berikut agar super admin dapat memisahkan laporan token dan biaya per fitur:

```text
discussion
learning_note
learning_recommendation
learning_question
learning_flashcard
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

### 5.5 AI Learning Tools pada halaman pembahasan

AI Learning Tools terdiri dari **Catatan**, **Rekomendasi**, **Soal Serupa**, dan **Flashcard**. Fitur ini dapat dipakai dari dua sumber:

1. **Dari pembahasan soal**: konteks soal aktif diambil ulang oleh server project penerima setelah validasi attempt dan akses tryout.
2. **Mandiri dari menu AI Learning Tools**: peserta mengisi topik atau materi sendiri. Browser tetap mengirim ke route project penerima; server project penerima yang memanggil gateway pusat.

Alurnya selalu sama:

```text
Browser peserta
    -> server project penerima
    -> BimbelHub AI Gateway pusat (/api/ai-gateway/discussion)
    -> provider AI (Gemini/OpenAI)
```

Browser **tidak pernah** memanggil `demo.bimbelhub.com`, server production BimbelHub, Gemini, atau OpenAI secara langsung. `AI_GATEWAY_URL` menunjuk gateway pusat (domain demo hanya untuk environment demo); API key provider dan `AI_GATEWAY_KEY` hanya berada di server.

Keempat tool menggunakan `POST /discussion` secara server-to-server. Project penerima membentuk instruksi terstruktur dan mengirim nilai `feature` yang sesuai. Gateway tetap memvalidasi subscription/trial, memotong kuota, serta mencatat token dan biaya per fitur. Jangan membuat endpoint provider AI langsung di project penerima.

Nilai `feature` juga menjadi sumber analytics super admin. Gateway menghitung jumlah request, total token, jumlah peserta unik, dan persentase penggunaan untuk Diskusi Soal, Catatan, Rekomendasi, Soal Serupa, serta Flashcard. Karena itu, jangan mengirim semua tool sebagai `discussion` dan jangan mengandalkan event JavaScript untuk statistik penggunaan.

| Tool | `feature` | Hasil yang wajib dinormalisasi server |
|---|---|---|
| Catatan | `learning_note` | judul, ringkasan, section, poin penting, istilah/rumus |
| Rekomendasi | `learning_recommendation` | fokus belajar, alasan, prioritas, dan urutan belajar |
| Soal Serupa | `learning_question` | daftar soal, empat opsi, jawaban benar, pembahasan, metadata kesulitan/HOTS |
| Flashcard | `learning_flashcard` | judul set dan pasangan `front`/`back` |

#### Kontrak hasil terstruktur

Model dapat mengembalikan JSON dalam field `message` atau format yang telah disepakati gateway. Project penerima wajib memvalidasi dan menyimpan bentuk akhirnya sebelum ditampilkan. Jangan merender respons mentah model sebagai HTML.

Contoh minimal **Catatan**:

```json
{
  "title": "Materi Antonim",
  "summary": "Antonim adalah kata dengan makna berlawanan.",
  "sections": [
    {
      "title": "Konsep inti",
      "paragraphs": ["Optimis berarti berpandangan baik."],
      "bullets": ["Pesimis adalah antonim optimis."]
    }
  ],
  "key_points": ["Bandingkan makna inti setiap pilihan."],
  "formulas": []
}
```

Catatan harus dirender sebagai materi yang mudah dibaca: ringkasan singkat, section, bullet, dan highlight istilah/rumus bila ada; bukan satu paragraf panjang.

Contoh minimal **Rekomendasi**:

```json
{
  "title": "Prioritas belajar antonim",
  "focus_topics": [
    {"topic": "Makna kata", "reason": "Dasar mencari antonim", "priority": "tinggi"}
  ],
  "study_plan": ["Pelajari konsep", "Kerjakan latihan", "Evaluasi kesalahan"]
}
```

Model tidak boleh membuat URL video, modul, atau referensi eksternal. Server project penerima mencocokkan `focus_topics` dengan materi aktif di database sendiri, lalu hanya merender materi/referensi yang telah disetujui admin.

Contoh minimal **Soal Serupa**:

```json
{
  "title": "Latihan antonim",
  "questions": [
    {
      "question_text": "Antonim kata ... adalah ...",
      "options": [
        {"key": "A", "text": "..."},
        {"key": "B", "text": "..."},
        {"key": "C", "text": "..."},
        {"key": "D", "text": "..."}
      ],
      "correct_answer": "A",
      "explanation": "...",
      "difficulty": "sedang",
      "hots_level": "sedang"
    }
  ]
}
```

Server harus memastikan jumlah hasil sesuai pilihan peserta, pilihan jawaban lengkap, jawaban benar menunjuk opsi yang ada, dan nilai kesulitan/variasi/HOTS termasuk allowlist. Jumlah soal dibatasi oleh kebijakan produk dan estimasi token yang tersisa; tampilkan batas maksimum sebelum generate.

Contoh minimal **Flashcard**:

```json
{
  "title": "Kartu istilah antonim",
  "cards": [
    {"front": "Apa arti antonim?", "back": "Kata yang maknanya berlawanan."}
  ]
}
```

#### Perilaku UI yang wajib

- Sediakan halaman **AI Learning Tools** di navbar. Halaman dibuka pada **Paket & Kuota** sebagai menu/default pertama, kemudian menu Catatan, Rekomendasi, Soal Serupa, dan Flashcard.
- Gunakan sidebar tool yang konsisten. Setiap menu memiliki area generate dan area **Riwayat** dengan tab **Riwayat** dan **Pin**. Riwayat hanya menampilkan artifact tool yang sedang dibuka.
- Jangan otomatis menampilkan hasil generate panjang di bawah form. Setelah sukses tampilkan status dan tombol **Lihat hasil**; detail artifact, termasuk artifact riwayat, dibuka dalam modal.
- Modal detail memiliki tinggi wajar, header tetap, dan area isi yang scroll di dalam modal. Jangan membuat seluruh halaman/modal luar ikut scroll.
- Artifact dapat dipin atau unpin oleh pemiliknya. Status pin tampil di sisi kanan kartu dan berubah menjadi aksi Pin/Unpin saat hover/focus.
- Dari pembahasan soal, tool dan riwayat memakai kontrak serta action yang sama. Hasil dari Catatan tidak boleh tampil pada tab Soal Serupa, Flashcard, atau Rekomendasi.
- Flashcard menampilkan kartu set lebih dahulu. Tombol **Preview/Mulai recall** membuka modal recall di atas modal hasil; ketika recall berlangsung, navigasi halaman/modal dasar tidak boleh aktif. Membuka hasil lama atau berpindah kartu tidak memanggil gateway lagi.
- Catatan yang dipin dapat diekspor PDF sesuai kebijakan project. Aksi **Perdalam materi** dapat membuat catatan baru dari artifact catatan yang tersimpan; ini adalah generate baru dan tetap memakai token.

#### Aturan generate dan pendalaman

1. Terapkan kembali seluruh validasi akses pada Bagian 8 sebelum generate yang bersumber dari pembahasan.
2. Konteks soal, jawaban, dan pembahasan harus diambil dari database server; jangan percaya payload konteks dari browser.
3. Untuk mode mandiri, browser hanya mengirim `tool`, topik/isi, dan opsi yang diizinkan. Batasi topik/isi, misalnya maksimum 10.000 karakter; identitas pengguna selalu dari session server.
4. Respons tool wajib dinormalisasi dan divalidasi server sebelum disimpan/dirender.
5. Saat memperdalam catatan, browser hanya boleh mengirim ID artifact miliknya dan fokus tambahan singkat (misalnya maksimum 300 karakter). Server mengambil payload catatan asli dari database, membentuk konteks tepercaya, lalu membuat artifact catatan baru.
6. Setiap generate, termasuk pendalaman, tetap mengurangi token gateway, termasuk untuk paket gratis. Membuka riwayat, preview, pin/unpin, dan ekspor PDF tidak boleh memanggil AI lagi.
7. Jangan melakukan retry otomatis untuk request generate yang gagal/timeout karena berisiko memotong token dua kali. Tampilkan tombol coba lagi agar peserta memutuskan sendiri.
8. Artifact lokal harus terikat ke `user_id`; lihat detail, pin, ekspor PDF, pendalaman, dan hapus harus menolak artifact milik user lain.

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
   Jika paket gratis dan berbayar tersedia bersamaan, UI pembelian produk harus memilih paket berbayar sebagai default. Jika hanya paket gratis yang tersedia, tampilkan placeholder **Pilih paket AI** agar paket gratis tetap dipilih secara sadar oleh user.
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
POST /user/.../pembahasan-ai/tools  membuat catatan, rekomendasi, soal serupa, atau flashcard
GET  /user/.../pembahasan-ai/tools/history  riwayat tool untuk soal/attempt tersebut
GET  /user/ai-learning-tools        halaman mandiri (`?tool=quota|note|recommendation|question|flashcard`)
POST /user/ai-learning-tools/generate membuat artifact tool dari topik/materi mandiri
GET  /user/ai-learning-tools/{artifact} detail artifact milik user
PATCH /user/ai-learning-tools/{artifact}/pin mengubah status pin artifact milik user
POST /user/tryout/{tryout}/buy       checkout produk CPNS, opsional combined AI
GET  /user/payment/{transaction}/resume  menampilkan ulang dua pembayaran pending setelah validasi kepemilikan
GET  /user/catatan-ai               daftar artifact Catatan milik user
POST /user/catatan-ai/{artifact}/pin mengubah status pin Catatan milik user
POST /user/catatan-ai/{artifact}/expand memperdalam catatan dari artifact milik user
GET  /user/catatan-ai/{artifact}/pdf mengekspor catatan milik user sebagai PDF
```

Semua route harus memakai middleware `auth` dan rate limit. Rekomendasi:

```php
Route::post('/.../pembahasan-ai/chat', ...)
    ->middleware(['auth', 'throttle:12,1']);

Route::post('/user/paket-ai/checkout', ...)
    ->middleware(['auth', 'throttle:20,1']);

Route::post('/user/ai-learning-tools/generate', ...)
    ->middleware(['auth', 'throttle:12,1']);

Route::post('/user/catatan-ai/{artifact}/expand', ...)
    ->middleware(['auth', 'throttle:8,1']);
```

Nama route dapat disesuaikan, tetapi route detail/action artifact wajib memakai route model binding atau query yang memverifikasi kepemilikan user login. Gunakan `POST` untuk generate, jangan `GET`.

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
- Default pilihan add-on harus paket berbayar pertama. Jika tidak ada paket berbayar, tampilkan placeholder **Pilih paket AI**; jangan pernah otomatis memilih paket gratis.
- Saat submit, nonaktifkan tombol sampai kedua request server selesai agar tidak terjadi invoice dobel.
- Bila salah satu checkout gagal, tampilkan error dan jangan menampilkan akses aktif apa pun.
- Bila dua pembayaran berhasil dibuat, tampilkan halaman/modal ringkasan dengan dua link pembayaran dan label status masing-masing.
- Saat halaman di-refresh, status pending harus dipulihkan dari server (`combined_ai_checkout` yang tervalidasi + `/subscription`), bukan hanya state JavaScript.

### Halaman paket AI

- Ambil paket dari `/plans`.
- Ambil status dari `/subscription`.
- Saat checkout berhasil, redirect ke `invoice_url`.
- Saat kembali dari provider, refresh status dari gateway sebelum merender tombol.

### Halaman AI Learning Tools mandiri

- Tambahkan satu menu navbar **AI Learning Tools**. Jangan membuat menu terpisah untuk masing-masing tool.
- Gunakan satu layout dengan sidebar internal: **Paket & Kuota**, **Catatan**, **Rekomendasi**, **Soal Serupa**, dan **Flashcard**. Paket & Kuota adalah tampilan default.
- Setiap halaman tool memuat form input yang relevan dan daftar artifact milik user pada panel yang sama. Jangan menyediakan tombol “buat baru” global; generate dilakukan langsung dari tab tool yang dipilih.
- Catatan, Rekomendasi, Soal Serupa, dan Flashcard masing-masing mempunyai tab **Riwayat** dan **Pin**. Filter sumber (misalnya paket/tryout atau mandiri) hanya menampilkan artifact milik user sendiri.
- Setelah generate sukses, tampilkan tombol **Lihat hasil**. Tampilkan hasil detail dalam modal, termasuk action yang sesuai seperti Pin/Unpin, Preview recall, ekspor PDF, atau Perdalam materi.
- Untuk Soal Serupa, tampilkan pengaturan jumlah soal, kesulitan, variasi, dan level HOTS hanya setelah peserta memilih aksi **Buat soal serupa**, lalu validasi kembali semuanya di server.
- Untuk Flashcard, tampilkan ringkasan set dan tombol preview. Mode recall harus memisahkan pertanyaan dan jawaban secara visual serta tidak mengaktifkan navbar/modal dasar selama sedang berlangsung.

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

Untuk AI Learning Tools, simpan artifact lokal terpisah agar hasil terstruktur tidak dipaksakan ke tabel chat, minimal:

```text
user_id
tryout_id                       # nullable untuk mode mandiri
question_id                     # nullable untuk mode mandiri
attempt_token                   # nullable untuk mode mandiri
source_type                     # discussion atau independent
source_label                    # label paket/tryout/topik untuk filter riwayat
tool                            # note, recommendation, question, flashcard
title
payload                        # JSON hasil yang sudah dinormalisasi server
provider / model
input_tokens / output_tokens / total_tokens
pinned_at                       # null bila belum dipin
timestamps
```

Artifact lokal hanya untuk histori, pin, modal detail, recall flashcard, dan ekspor PDF. Jangan menggandakan status subscription gateway di tabel ini. Pemakaian token tetap dicatat melalui gateway dan artifact lokal tidak boleh dijadikan sumber keputusan kuota.

## 11. Checklist pengujian sebelum rilis

- [ ] Key gateway tidak muncul di HTML, JavaScript, response user, atau repository.
- [ ] User A tidak bisa chat memakai `question_id` atau `attempt_token` milik user B.
- [ ] User tidak bisa mengirim konteks soal buatan dari browser.
- [ ] User tanpa trial/paket mendapat 403 dan tombol beli paket.
- [ ] User dengan kuota habis mendapat 429 dan tombol beli/perpanjang.
- [ ] Checkout kedua sebelum 24 jam memakai invoice pending yang sama.
- [ ] Setelah redirect sukses, tombol belum menjadi **Mulai** sebelum `/subscription` menyatakan paket aktif.
- [ ] Response pengecekan provider yang sukses tetapi status transaksinya masih pending tidak mengaktifkan paket AI.
- [ ] Redirect checkout ditolak bila bukan origin CPNS Academy.
- [ ] Chat dibatasi rate limit dan pesan maksimal 1.200 karakter.
- [ ] Jika gateway timeout, UI gagal dengan aman dan user dapat mencoba lagi.
- [ ] Double click/dua tab tidak menghasilkan dua transaksi produk atau dua invoice AI yang terlihat aktif.
- [ ] Produk lunas tetapi AI pending: tryout terbuka, chat AI tetap terkunci.
- [ ] AI aktif tetapi produk pending: endpoint chat tetap menolak karena user belum memiliki akses tryout.
- [ ] Refresh/return dari pembayaran tidak pernah membuat tombol **Mulai** sebelum status produk dan AI masing-masing tervalidasi.
- [ ] Resume pembayaran menolak transaction ID, tryout ID, atau invoice milik user lain.
- [ ] AI Learning Tools menolak attempt token atau `question_id` milik user lain.
- [ ] Mode mandiri tidak pernah memanggil BimbelHub/provider dari JavaScript; request selalu melalui server project penerima.
- [ ] Rekomendasi belajar tidak pernah merender URL yang dibuat AI; hanya materi/referensi aktif yang disetujui admin.
- [ ] Detail, pin/unpin, pendalaman, ekspor PDF, dan hapus artifact menolak artifact milik user lain.
- [ ] Generate catatan, rekomendasi, soal, dan flashcard tetap menambah pemakaian token gateway, termasuk pada paket gratis.
- [ ] Membuka riwayat, modal hasil, preview/recall flashcard, dan pin/unpin tidak memanggil generate AI atau menambah token.
- [ ] Hasil tiap tool hanya muncul dalam tab tool-nya sendiri; tab Pin juga hanya memuat artifact tool yang aktif.
- [ ] Hasil tool terstruktur tervalidasi sebelum dirender; soal serupa memiliki opsi/jawaban benar yang valid dan catatan tidak dirender sebagai satu paragraf mentah.
- [ ] Timeout generate ditampilkan sebagai kegagalan aman dengan aksi coba lagi, tanpa retry otomatis.

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
