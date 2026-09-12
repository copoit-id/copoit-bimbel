# Rancangan Asisten AI Admin Berbasis Kondisi Project

## Status dan tujuan

Dokumen ini adalah rancangan implementasi **fase desain**. Belum ada perubahan perilaku asisten yang berjalan di production.

Tujuannya adalah mengembangkan Asisten Admin yang saat ini hanya menjawab ringkasan data menjadi asisten yang dapat:

1. menjawab data operasional yang admin memang berhak lihat;
2. menjelaskan cara memakai fitur pada project yang sedang dipasang; dan
3. memberi tautan navigasi serta sumber jawaban yang dapat diperiksa.

Asisten tidak boleh menebak fitur. Bila sumber tepercaya tidak cukup, ia harus mengatakan bahwa informasi belum tersedia dan menawarkan langkah verifikasi—bukan membuat instruksi fiktif.

## Kondisi saat ini

Komponen yang sudah ada dan harus menjadi basis pengembangan:

| Komponen | Lokasi | Peran saat ini |
|---|---|---|
| UI chat mengambang | `resources/views/components/admin/assistant/index.blade.php` | Mengirim satu pertanyaan, tanpa riwayat tersimpan |
| Endpoint chat | `POST /{portal}/assistant/chat` | Sudah di belakang `auth`, admin middleware, permission, dan no-cache |
| Controller | `app/Http/Controllers/admin/AdminAssistantController.php` | Validasi pesan maksimal 500 karakter |
| Service | `app/Services/AdminAssistantService.php` | Intent berbasis keyword dan query ringkasan data yang telah ditentukan |
| Feature flag | `client_profile.admin_assistant_enabled` | Mengaktifkan/mematikan asisten untuk admin non-tutor |

Versi baru harus **memperluas `AdminAssistantService`**, bukan membuat jalur chat kedua. Fallback data lokal yang ada tetap dipertahankan agar pertanyaan dashboard yang sederhana tetap cepat dan tidak selalu membutuhkan LLM.

## Prinsip desain

1. **Grounded, bukan “tahu semua”.** Jawaban prosedural berasal dari dokumentasi fitur dan manifest kemampuan project. Jawaban data berasal dari tool query terkurasi.
2. **Kondisi project adalah sumber kebenaran.** Feature flag, role/permission, route, konfigurasi aktif yang aman, dan versi manifest selalu diambil server-side pada request saat ini.
3. **Least privilege.** LLM tidak menerima API key, password, token, webhook secret, data pembayaran mentah, PII lengkap, atau akses database/shell langsung.
4. **Read-only dahulu.** Fase pertama hanya menjawab dan memberi navigasi. Tidak ada aksi create/update/delete dari chat.
5. **Jawaban dapat diaudit.** Setiap jawaban memuat sumber, waktu snapshot, tingkat keyakinan, model, pemakaian token, dan ID audit internal.
6. **Kegagalan aman.** Jika LLM, retrieval, atau tool gagal, jangan menjalankan tindakan dan jangan membuat jawaban berdasarkan data lama tanpa penanda jelas.

## Arsitektur yang direkomendasikan

```text
Admin browser
  -> POST /{portal}/assistant/chat
  -> AdminAssistantController
  -> AdminAssistantOrchestrator
       -> Authorization + rate limit + audit start
       -> ProjectContextSnapshotService (fitur/izin/kondisi aktif)
       -> KnowledgeRetriever (panduan project dan manifest)
       -> DataToolRegistry (query data read-only yang diizinkan)
       -> LLM provider adapter (Responses API / provider yang dipilih)
       -> Response verifier + citation formatter
  -> JSON: jawaban, sumber, confidence, navigasi aman
```

Untuk OpenAI, gunakan **Responses API**. API ini mendukung tool bawaan seperti file search dan custom function calls; custom function harus dipetakan ke service Laravel yang dibatasi, bukan akses database bebas. Batasi `max_output_tokens`, `max_tool_calls`, dan gunakan structured output agar response selalu memiliki format yang tervalidasi. [OpenAI Responses API](https://developers.openai.com/api/reference/cli/resources/responses/methods/create)

### Mengapa bukan memberi seluruh source code ke LLM?

Repo penuh terlalu besar, dapat membawa secret, mudah kedaluwarsa, mahal, dan tidak menjamin model mengetahui fitur yang sedang aktif. Sebagai gantinya, server menyediakan **manifest terkurasi** dan dokumen per fitur. Hanya potongan yang relevan terhadap pertanyaan yang boleh masuk konteks.

## Sumber pengetahuan

### 1. Manifest kemampuan project (wajib)

Buat artefak versi, misalnya `storage/app/admin-assistant/manifest.json`, yang dihasilkan pada deploy dan dapat di-refresh oleh super admin. Contoh isi aman:

```json
{
  "schema_version": 1,
  "generated_at": "2026-08-28T10:30:00+07:00",
  "git_revision": "abc1234",
  "features": [
    {
      "key": "tryout",
      "enabled": true,
      "admin_permissions": ["tryout.view", "tryout.create"],
      "routes": {
        "index": "admin.tryout.index",
        "create": "admin.tryout.create"
      },
      "docs": ["tryout/create-and-publish.md"]
    }
  ]
}
```

Generator manifest hanya boleh membaca allowlist berikut:

- feature flags yang memang boleh dilihat admin;
- route name dan capability yang sudah terdaftar;
- permission/role yang sudah dinormalisasi;
- dokumentasi yang disetujui; dan
- revision aplikasi serta timestamp deploy.

Generator **tidak boleh** memasukkan environment variable, credential, isi file `.env`, konfigurasi provider, nomor rekening, token, atau source file bebas.

### 2. Dokumentasi prosedur per fitur (wajib sebelum fitur dinyalakan)

Simpan dalam `docs/admin-assistant/`. Setiap file harus memiliki front matter:

```yaml
---
feature: tryout
title: Membuat dan menerbitkan tryout
audience: [admin, super_admin]
required_permissions: [tryout.create]
routes: [admin.tryout.create, admin.tryout.index]
last_verified_at: 2026-08-28
verified_against_revision: abc1234
---
```

Isi dokumen mencakup prasyarat, langkah UI, hasil yang diharapkan, batasan, dan troubleshooting. Gunakan route name sebagai sumber navigasi—bukan URL yang di-hardcode.

Awal implementasi cukup gunakan lexical retrieval lokal (judul, feature, tag, dan full-text) agar mudah diaudit. File search/vector store dapat ditambahkan setelah corpus stabil. Retrieval harus memfilter `audience`, `required_permissions`, serta feature yang benar-benar aktif sebelum konteks dikirim ke model.

### 3. Snapshot kondisi saat ini (per request)

`ProjectContextSnapshotService` mengirim fakta runtime yang telah disaring:

- portal (`admin` atau `tutor`), role dan permission efektif pemohon;
- daftar fitur aktif yang boleh dilihat pemohon;
- nama project, timezone, locale, dan revision manifest;
- navigasi yang diizinkan untuk pemohon; dan
- status data ringan yang relevan jika tool telah dipanggil.

Nilai runtime mengalahkan dokumentasi statis. Contoh: dokumentasi paket tersedia, tetapi jika feature flag paket mati atau pemohon tidak punya `package.create`, asisten menjelaskan bahwa fitur tidak tersedia bagi akun tersebut.

## Tool server-side yang diizinkan

LLM boleh memilih tool dengan schema ketat. Semua tool menerima konteks admin dari server, **bukan ID/role dari input browser**.

| Tool | Kegunaan | Batasan |
|---|---|---|
| `search_feature_guides` | Mencari panduan prosedural | Hanya dokumen yang lolos permission + feature filter; hasil mengandung ID sumber |
| `get_feature_status` | Memastikan fitur/route/permission aktif | Hanya key fitur dari allowlist |
| `get_navigation_link` | Menghasilkan CTA menuju halaman yang diizinkan | Route name allowlist; cek authorization sebelum URL dibuat |
| `get_operational_summary` | Ringkasan data seperti pembayaran pending atau peserta baru | Intent/query parameter allowlist; reuse query service data saat ini; tidak menerima SQL |
| `get_current_page_help` | Bantuan kontekstual untuk halaman admin saat ini | Page key allowlist dari route yang sedang diakses |

Tool tidak boleh menyediakan:

- `run_sql`, shell, filesystem read, HTTP arbitrer, atau evaluasi kode;
- data user tunggal kecuali izin khusus dan kebutuhan operasional jelas;
- create/update/delete/approve/refund/export; atau
- pembacaan credential dan konfigurasi rahasia.

Setelah fase read-only stabil, aksi dapat ditambahkan sebagai tool terpisah dengan pola: **rencana aksi -> ringkasan dampak -> konfirmasi eksplisit -> policy/permission check ulang -> transaksi -> audit log**. Jangan pernah mengandalkan konfirmasi teks model sebagai otorisasi.

## Kontrak respons

Controller mengembalikan struktur berikut, bukan teks bebas saja:

```json
{
  "answer": "Untuk membuat tryout, buka menu Tryout lalu pilih Tambah Tryout...",
  "answer_type": "how_to",
  "confidence": "verified",
  "sources": [
    {
      "type": "guide",
      "id": "tryout/create-and-publish",
      "title": "Membuat dan menerbitkan tryout",
      "verified_at": "2026-08-28"
    },
    {
      "type": "runtime",
      "id": "feature:tryout",
      "title": "Tryout aktif untuk peran Anda"
    }
  ],
  "actions": [
    {
      "label": "Buka Tryout",
      "route": "admin.tryout.index"
    }
  ],
  "audit_id": "aasst_..."
}
```

Nilai `confidence` hanya boleh salah satu dari berikut:

| Nilai | Syarat tampilan |
|---|---|
| `verified` | Jawaban memiliki minimal satu sumber dokumen/manifest yang sesuai dan seluruh klaim fitur cocok dengan snapshot runtime. |
| `data_verified` | Angka berasal dari tool query dan mencantumkan rentang waktu serta waktu pengambilan. |
| `partial` | Ada konteks relevan, tetapi tidak cukup untuk seluruh pertanyaan; jawaban wajib menyebut bagian yang belum pasti. |
| `unknown` | Tidak ada sumber cukup; asisten tidak memberi langkah seolah-olah pasti. |

UI perlu menampilkan sumber, label confidence, CTA yang aman, serta tombol “Jawaban ini tidak membantu/keliru”. Jangan hanya memakai `x-text` untuk jawaban baru; render Markdown melalui sanitizer dengan allowlist agar tautan/sumber dapat ditampilkan tanpa XSS.

## Instruksi model dan anti-halusinasi

System instruction minimum:

```text
Anda adalah Asisten Admin BIMBELHUB. Gunakan hanya fakta dari PROJECT SNAPSHOT,
GUIDE SOURCES, dan TOOL RESULTS. Jangan menyebut menu, route, permission, angka,
atau konfigurasi yang tidak ada di sumber. Bila sumber tidak cukup, jawab
"Saya belum bisa memverifikasi hal itu di project ini" dan arahkan ke admin/developer.
Jangan pernah meminta atau mengungkap credential, token, password, atau data pribadi.
Jangan melakukan atau menyarankan aksi yang mengubah data; berikan langkah dan CTA
read-only yang tersedia. Sertakan source IDs untuk setiap klaim prosedural atau data.
```

Tambahkan validasi server setelah respons model:

1. parse structured response sesuai JSON schema;
2. setiap `source_id` harus berasal dari context request;
3. setiap CTA route harus dari hasil `get_navigation_link`;
4. blok kata/field rahasia dan URL eksternal yang tidak diizinkan;
5. jika validasi gagal, ubah respons menjadi `unknown` dan jangan tampilkan isi model mentah.

## Keamanan, privasi, dan biaya

- Rate limit per admin dan per tenant, misalnya 20 pertanyaan/10 menit; sediakan limit harian token dan maksimum 500 karakter input.
- Gunakan `safety_identifier` yang merupakan hash ID pengguna, bukan email/nama mentah. [OpenAI Responses API](https://developers.openai.com/api/reference/cli/resources/responses/methods/create)
- Simpan audit metadata minimum: admin ID, role snapshot, hash pesan, source IDs, tool names, model/provider, token usage, latency, outcome, dan feedback. Isi pertanyaan boleh dienkripsi/retention terbatas sesuai kebijakan privacy.
- Redact PII sebelum konteks dikirim. Untuk metrik gunakan agregat; nama/email peserta tidak perlu untuk pertanyaan operasional umum.
- API key tetap server-side dan encrypted seperti konfigurasi AI yang sudah ada. Jangan kirim ke Blade/JavaScript/log.
- Terapkan timeout, retry terbatas untuk error jaringan, circuit breaker, dan fallback ke intent data lokal yang sudah ada.
- Rekam token input/output dari respons dan pasang budget alert per project/bulan.

## Tahapan implementasi

### Fase 0 — Fondasi dokumen dan evaluasi

1. Buat 10–15 panduan fitur prioritas di `docs/admin-assistant/`.
2. Buat manifest generator dan command `admin-assistant:build-manifest`.
3. Susun dataset evaluasi minimal 100 pertanyaan: how-to, data, fitur nonaktif, tanpa izin, pertanyaan tak dikenal, dan prompt injection.
4. Tentukan acceptance gate: 100% CTA harus authorized; 100% jawaban fitur harus punya source; 0 kebocoran secret/PII; dan tingkat jawaban prosedural benar berdasarkan review internal sesuai target yang disepakati.

### Fase 1 — Knowledge assistant read-only

1. Tambahkan tabel conversation/audit dan konfigurasi asisten terpisah dari feature flag lama.
2. Bangun snapshot, retriever lokal, schema tools, provider adapter, response verifier, dan rate limit.
3. Perbarui UI agar mendukung source/confidence/CTA/feedback dan riwayat percakapan singkat.
4. Luncurkan hanya ke super admin melalui feature flag dan audit setiap jawaban.

### Fase 2 — Data tools dan kualitas

1. Migrasikan intent data yang ada menjadi `get_operational_summary` agar hasil tetap deterministik.
2. Tambahkan pagination/aggregate aman bila diperlukan.
3. Jalankan eval otomatis setiap perubahan manifest/dokumen/prompt/model.
4. Tambahkan dashboard quality: feedback negatif, `unknown` rate, tool error, latency, token, dan pertanyaan tanpa dokumen.

### Fase 3 — Aksi terkontrol (opsional, tidak termasuk MVP)

Mulai dari action yang reversibel dan berisiko rendah. Setiap action wajib punya permission spesifik, parameter tervalidasi, preview, konfirmasi UI terpisah, idempotency key, transaction, dan audit. Aksi finansial, hak akses, dan penghapusan memerlukan rancangan/persetujuan terpisah.

## Data model yang diusulkan

| Tabel | Field inti | Tujuan |
|---|---|---|
| `admin_assistant_conversations` | id, user_id, portal, manifest_revision, last_response_id, timestamps | Riwayat percakapan terbatas dan terisolasi per admin |
| `admin_assistant_messages` | conversation_id, role, encrypted_content, source_ids, token_usage, model, latency_ms | Audit jawaban dan konteks percakapan |
| `admin_assistant_feedback` | message_id, user_id, rating, reason, note | Bahan evaluasi kualitas |
| `admin_assistant_audits` | user_id, request_hash, tool_calls, outcome, manifest_revision, timestamps | Audit keamanan yang immutable/seperlunya |

Migration harus production-safe: cek keberadaan tabel/kolom sebelum menambahkannya, index-kan `user_id`, `conversation_id`, dan `created_at`, serta jangan menyimpan secret.

## Keputusan yang perlu disetujui sebelum coding

1. Provider MVP: OpenAI saja, atau adapter OpenAI + Gemini dari awal.
2. Siapa pengguna awal: super admin saja (rekomendasi), atau seluruh admin yang mempunyai permission baru `admin_assistant.use`.
3. Retensi percakapan/audit dan kebijakan PII sesuai kebutuhan bisnis.
4. Daftar 10–15 fitur yang dokumentasinya diprioritaskan.
5. Target evaluasi dan budget token bulanan.

## Kriteria selesai MVP

MVP dinyatakan siap bila:

- admin berizin dapat bertanya data dan cara memakai fitur aktif;
- setiap jawaban prosedural memunculkan sumber dan tidak menawarkan fitur yang nonaktif/tidak berizin;
- pertanyaan tanpa sumber mendapat respons jujur `unknown`;
- tidak ada tool dengan SQL/shell/filesystem/HTTP arbitrer;
- audit, rate limit, feedback, dan budget guard berjalan;
- test authorization, redaction, manifest, tool schema, verifier, dan eval regression lulus; serta
- rollout super-admin stabil sebelum akses diperluas.
