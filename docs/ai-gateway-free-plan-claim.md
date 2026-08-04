# Klaim Paket Pembahasan AI Gratis

Paket AI dengan `price = 0` adalah paket gratis. Peserta mengklaim paket melalui server project client, lalu AI Gateway langsung mengaktifkan subscription tanpa membuat invoice atau membuka payment gateway.

## Aturan paket gratis

- `price = 0`: paket gratis dan dapat diklaim langsung.
- `token_limit` wajib lebih dari `0` dan menjadi batas utama penggunaan.
- `chat_limit = 0`: jumlah chat unlimited selama token masih tersedia.
- `duration_days = 0`: paket tidak kedaluwarsa.
- Satu `external_user_id` hanya dapat mengklaim paket gratis yang sama satu kali pada project client yang sama.
- Klaim peserta di satu project tidak memberikan akses kepada peserta atau project lain.

## Daftar paket

`GET /api/ai-gateway/plans` menambahkan penanda `is_free`:

```json
[
  {
    "id": 7,
    "name": "Pembahasan AI Gratis",
    "slug": "pembahasan-ai-gratis",
    "price": 0,
    "is_free": true,
    "token_limit": 10000,
    "chat_limit": 0,
    "duration_days": 30
  }
]
```

UI peserta harus menampilkan tombol **Klaim Gratis** bila `is_free === true`. Paket berbayar tetap menampilkan tombol pembayaran.

## Klaim dari server client

Browser tidak boleh memanggil AI Gateway langsung. Browser memanggil route project client yang sudah login, kemudian server client meneruskan request berikut:

```http
POST /api/ai-gateway/checkout
X-AI-Gateway-Key: aigw_xxx
Accept: application/json
Content-Type: application/json
```

```json
{
  "plan_id": 7,
  "external_user_id": "123",
  "customer_name": "Nama Peserta",
  "customer_email": "peserta@example.com"
}
```

Response klaim pertama:

```json
{
  "message": "Paket gratis berhasil diklaim dan langsung aktif.",
  "activated": true,
  "claimed": true,
  "already_claimed": false,
  "invoice_url": null,
  "external_id": "AIGW-FREE-...",
  "subscription": {
    "status": "active",
    "starts_at": "2026-07-17T10:00:00.000000Z",
    "ends_at": "2026-08-16T10:00:00.000000Z"
  }
}
```

Response bila tombol diklik ulang memakai peserta dan paket yang sama tetap HTTP 200 serta mengembalikan subscription yang sama:

```json
{
  "activated": true,
  "claimed": false,
  "already_claimed": true,
  "invoice_url": null,
  "message": "Paket gratis ini sudah pernah diklaim."
}
```

Client tidak boleh melakukan redirect bila `activated === true`. Refresh status subscription dan ubah tombol menjadi **Sudah diklaim** atau **Paket aktif**.

## Status peserta

Panggil:

```http
GET /api/ai-gateway/subscription?external_user_id=123
X-AI-Gateway-Key: aigw_xxx
```

Field `claimed_free_plan_ids` berisi ID paket gratis yang sudah pernah diklaim peserta, termasuk paket yang kuotanya telah habis atau masa aktifnya telah berakhir. Gunakan field ini agar tombol klaim tidak muncul kembali.

## Checklist client

- Ambil `external_user_id`, nama, dan email dari user login, bukan dari browser sebagai sumber tepercaya.
- Nonaktifkan tombol saat request klaim berjalan.
- Jangan mengharapkan `invoice_url` untuk paket gratis.
- Jangan menganggap `claimed = false` sebagai kegagalan bila `already_claimed = true`; tampilkan status yang dikembalikan gateway.
- Setelah klaim, panggil endpoint subscription lagi sebelum membuka chat.
- Endpoint chat tetap harus mengirim `external_user_id` yang sama.
