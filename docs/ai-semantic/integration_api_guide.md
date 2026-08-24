# Panduan Batch API untuk AI Semantic Similarity Service

Dokumentasi lengkap untuk menggunakan endpoint batch processing.

---

## 📋 Overview

Service ini menyediakan 2 mode batch processing:

| Mode | Endpoint | Cocok untuk | Karakteristik |
|------|----------|-------------|---------------|
| **Synchronous** | `/api/v1/similarity/batch` | < 50 jawaban, real-time | Tunggu hasil, 1-5 detik |
| **Asynchronous** | `/api/v1/similarity/batch-async` | > 50 jawaban, background | Job queue, callback, cek status |

---

## 🔗 Endpoint 1: Batch Sync (Synchronous)

### URL
```
POST /api/v1/similarity/batch
```

### Request Body

```json
{
  "user_id": "string (required) - ID user/peserta",
  "method": "string (optional) - semantic|jaccard|overlap|tfidf, default: semantic",
  "answers": [
    {
      "question_id": "string (required) - ID soal",
      "kunci": "string (required) - Kunci jawaban",
      "jawaban": "string (required) - Jawaban peserta"
    }
  ]
}
```

### Field Detail

| Field | Type | Required | Default | Deskripsi |
|-------|------|----------|---------|-----------|
| `user_id` | string | ✅ | - | ID unik user/peserta |
| `method` | string | ❌ | "semantic" | Metode perhitungan |
| `answers` | array | ✅ | - | List jawaban (max 100) |
| `answers[].question_id` | string | ✅ | - | ID soal |
| `answers[].kunci` | string | ✅ | - | Kunci jawaban |
| `answers[].jawaban` | string | ✅ | - | Jawaban peserta |

### Methods yang Tersedia

| Method | Deskripsi | Kapan Digunakan |
|--------|-----------|-----------------|
| `semantic` | AI semantic similarity | Default, paham sinonim |
| `jaccard` | Token intersection/union | Jawaban singkat, exact match |
| `overlap` | Persentase kata kunci | Cek kelengkapan poin |
| `tfidf` | Cosine similarity TF-IDF | Alternatif keyword |

### Response Sukses (200)

```json
{
  "user_id": "user123",
  "results": [
    {
      "question_id": "q1",
      "similarity": 0.95,
      "score": 95
    },
    {
      "question_id": "q2",
      "similarity": 0.88,
      "score": 88
    }
  ],
  "total_score": 91.5,
  "processed_count": 2,
  "processing_time_ms": 450,
  "method": "semantic"
}
```

### Response Field

| Field | Type | Deskripsi |
|-------|------|-----------|
| `user_id` | string | ID user dari request |
| `results` | array | List hasil per soal |
| `results[].question_id` | string | ID soal |
| `results[].similarity` | float | Score 0.0 - 1.0 |
| `results[].score` | integer | Score 0 - 100 |
| `total_score` | float | Rata-rata score |
| `processed_count` | integer | Jumlah jawaban diproses |
| `processing_time_ms` | integer | Waktu proses (ms) |
| `method` | string | Metode yang digunakan |

### Response Error (400)

```json
{
  "error": true,
  "message": "answers tidak boleh kosong"
}
```

### Response Error (500)

```json
{
  "error": true,
  "message": "Batch processing failed: [detail error]"
}
```

---

## 🔗 Endpoint 2: Batch Async (Asynchronous) - READY

### URL
```
POST /api/v1/similarity/batch-async
```

### Deskripsi
Proses banyak jawaban di background dengan **Celery + Redis**. Cocok untuk:
- 100-500 jawaban per user
- 1000+ user simultan
- Tidak perlu real-time

### Flow Async

```
1. Laravel kirim request → Dapat job_id langsung (0.1 detik)
2. AI proses di background queue (1-10 menit, tergantung antrian)
3. AI callback ke Laravel webhook saat selesai
4. Laravel bisa cek status kapan saja dengan /job-status/{job_id}
```

### Request Body

```json
{
  "user_id": "string (required) - ID user/peserta",
  "method": "string (optional) - semantic|jaccard|overlap|tfidf, default: semantic",
  "answers": [
    {
      "question_id": "string (required) - ID soal",
      "kunci": "string (required) - Kunci jawaban",
      "jawaban": "string (required) - Jawaban peserta"
    }
  ],
  "callback_url": "string (required) - URL webhook Laravel untuk callback"
}
```

### Field Detail

| Field | Type | Required | Default | Deskripsi |
|-------|------|----------|---------|-----------|
| `user_id` | string | ✅ | - | ID unik user/peserta |
| `method` | string | ❌ | "semantic" | Metode perhitungan |
| `answers` | array | ✅ | - | List jawaban (max 500) |
| `answers[].question_id` | string | ✅ | - | ID soal |
| `answers[].kunci` | string | ✅ | - | Kunci jawaban |
| `answers[].jawaban` | string | ✅ | - | Jawaban peserta |
| `callback_url` | string | ✅ | - | URL webhook Laravel |

### Response Sukses (200) - Langsung

```json
{
  "job_id": "550e8400-e29b-41d4-a716-446655440000",
  "status": "QUEUED",
  "message": "Job queued successfully. Processing will start soon.",
  "estimated_time_seconds": 120,
  "callback_url": "https://your-laravel-app.com/webhook/ai-callback"
}
```

### Response Field

| Field | Type | Deskripsi |
|-------|------|-----------|
| `job_id` | string | UUID unik untuk tracking |
| `status` | string | `QUEUED` - Sudah masuk antrian |
| `message` | string | Info status |
| `estimated_time_seconds` | integer | Estimasi waktu selesai |
| `callback_url` | string | URL callback yang akan dihit |

---

## 🔗 Endpoint 3: Cek Status Job Async

### URL
```
GET /job-status/{job_id}
```

### Response Status

| Status | Arti |
|--------|------|
| `PENDING` | Menunggu worker tersedia |
| `PROGRESS` | Sedang diproses (lihat `progress`) |
| `COMPLETED` | Selesai (lihat `result`) |
| `FAILED` | Gagal (lihat `error`) |

### Response Sukses (200)

**Status PENDING:**
```json
{
  "job_id": "550e8400-e29b-41d4-a716-446655440000",
  "status": "PENDING",
  "message": "Job is waiting in queue"
}
```

**Status PROGRESS:**
```json
{
  "job_id": "550e8400-e29b-41d4-a716-446655440000",
  "status": "PROGRESS",
  "progress": {
    "processed": 15,
    "total": 20
  }
}
```

**Status COMPLETED:**
```json
{
  "job_id": "550e8400-e29b-41d4-a716-446655440000",
  "status": "COMPLETED",
  "result": {
    "job_id": "550e8400-e29b-41d4-a716-446655440000",
    "user_id": "user123",
    "status": "COMPLETED",
    "results": [
      {"question_id": "q1", "similarity": 0.95, "score": 95},
      {"question_id": "q2", "similarity": 0.88, "score": 88}
    ],
    "total_score": 91.5,
    "processed_count": 2,
    "processing_time_ms": 1250,
    "method": "semantic"
  }
}
```

**Status FAILED:**
```json
{
  "job_id": "550e8400-e29b-41d4-a716-446655440000",
  "status": "FAILED",
  "error": "Connection timeout to AI model"
}
```

---

## 📞 Webhook Callback

### Format Callback (dari AI ke Laravel)

Saat job selesai, AI akan POST ke `callback_url`:

```http
POST https://your-laravel-app.com/webhook/ai-callback
Content-Type: application/json
```

```json
{
  "job_id": "550e8400-e29b-41d4-a716-446655440000",
  "user_id": "user123",
  "status": "COMPLETED",
  "results": [
    {
      "question_id": "q1",
      "similarity": 0.95,
      "score": 95
    },
    {
      "question_id": "q2",
      "similarity": 0.88,
      "score": 88
    }
  ],
  "total_score": 91.5,
  "processed_count": 20,
  "processing_time_ms": 4520,
  "method": "semantic"
}
```

### Webhook Handler di Laravel

```php
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Nilai;

class WebhookController extends Controller
{
    /**
     * Handle callback dari AI Service
     */
    public function aiCallback(Request $request)
    {
        $data = $request->validate([
            'job_id' => 'required|string',
            'user_id' => 'required|string',
            'status' => 'required|string',
            'results' => 'required|array',
            'total_score' => 'required|numeric',
        ]);
        
        // Simpan hasil ke database
        foreach ($data['results'] as $result) {
            Nilai::updateOrCreate(
                [
                    'user_id' => $data['user_id'],
                    'soal_id' => $result['question_id']
                ],
                [
                    'similarity' => $result['similarity'],
                    'score' => $result['score'],
                    'job_id' => $data['job_id'],
                    'status' => 'completed'
                ]
            );
        }
        
        // Update total score
        User::find($data['user_id'])->update([
            'total_score' => $data['total_score']
        ]);
        
        return response()->json(['status' => 'received']);
    }
}
```

### Route Laravel

```php
// routes/api.php
Route::post('/webhook/ai-callback', [WebhookController::class, 'aiCallback']);
```

---

## 💻 Contoh Implementasi

### Contoh 1: Kirim Batch Async (PHP/Laravel)

```php
<?php

use Illuminate\Support\Facades\Http;

class UjianController
{
    private string $aiServiceUrl = 'http://202.155.95.81:8000';
    
    /**
     * Submit jawaban untuk dinilai (Async)
     */
    public function submitJawaban(Request $request)
    {
        $userId = auth()->id();
        $jawabanList = $request->input('jawaban'); // Array jawaban
        
        // Format untuk AI
        $answers = [];
        foreach ($jawabanList as $jawaban) {
            $answers[] = [
                'question_id' => $jawaban['soal_id'],
                'kunci' => $jawaban['kunci_jawaban'],
                'jawaban' => $jawaban['jawaban_peserta']
            ];
        }
        
        // Kirim ke AI (Async)
        $response = Http::post("{$this->aiServiceUrl}/api/v1/similarity/batch-async", [
            'user_id' => $userId,
            'method' => 'semantic',
            'answers' => $answers,
            'callback_url' => route('webhook.ai-callback')
        ]);
        
        if ($response->failed()) {
            return response()->json([
                'error' => 'Gagal submit ke AI'
            ], 500);
        }
        
        $result = $response->json();
        
        // Simpan job_id untuk tracking
        JobTracking::create([
            'user_id' => $userId,
            'job_id' => $result['job_id'],
            'status' => 'queued',
            'estimated_completion' => now()->addSeconds($result['estimated_time_seconds'])
        ]);
        
        return response()->json([
            'message' => 'Jawaban sedang dinilai',
            'job_id' => $result['job_id'],
            'estimated_seconds' => $result['estimated_time_seconds'],
            'check_status_url' => url("/api/job-status/{$result['job_id']}")
        ]);
    }
    
    /**
     * Cek status penilaian
     */
    public function cekStatus($jobId)
    {
        $response = Http::get("{$this->aiServiceUrl}/job-status/{$jobId}");
        
        return response()->json($response->json());
    }
}
```

### Contoh 2: cURL (Async)

```bash
# Submit job async
curl -X POST http://202.155.95.81:8000/api/v1/similarity/batch-async \
  -H "Content-Type: application/json" \
  -d '{
    "user_id": "peserta_001",
    "method": "semantic",
    "answers": [
      {"question_id": "soal_1", "kunci": "Pancasila", "jawaban": "Pancasila adalah dasar negara"},
      {"question_id": "soal_2", "kunci": "1945", "jawaban": "Tahun 1945"}
    ],
    "callback_url": "https://your-app.com/webhook/ai-callback"
  }'

# Response: {"job_id": "xxx", "status": "QUEUED", ...}

# Cek status (ulangi sampai COMPLETED)
curl http://202.155.95.81:8000/job-status/xxx
```

### Contoh 3: JavaScript/Fetch (Async)

```javascript
async function submitJawabanAsync(userId, answers) {
  // Submit job
  const response = await fetch('http://202.155.95.81:8000/api/v1/similarity/batch-async', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
      user_id: userId,
      method: 'semantic',
      answers: answers.map(a => ({
        question_id: a.soalId,
        kunci: a.kunci,
        jawaban: a.jawaban
      })),
      callback_url: 'https://your-app.com/webhook/ai-callback'
    })
  });
  
  const { job_id, estimated_time_seconds } = await response.json();
  
  // Tampilkan ke user
  alert(`Jawaban sedang dinilai. Estimasi: ${estimated_time_seconds} detik`);
  
  // Poll status setiap 10 detik
  const checkStatus = setInterval(async () => {
    const statusRes = await fetch(`http://202.155.95.81:8000/job-status/${job_id}`);
    const status = await statusRes.json();
    
    if (status.status === 'COMPLETED') {
      clearInterval(checkStatus);
      console.log('Hasil:', status.result);
      alert('Penilaian selesai!');
    } else if (status.status === 'FAILED') {
      clearInterval(checkStatus);
      alert('Penilaian gagal: ' + status.error);
    }
  }, 10000);
  
  return job_id;
}
```

---

## ⚡ Performance & Limitasi

### Limitasi Async

| Parameter | Limit | Keterangan |
|-----------|-------|------------|
| Max answers | 500 | Lebih dari itu, split ke multiple job |
| Max jobs in queue | Unlimited | Tergantung Redis memory |
| Worker concurrency | 2-4 | Bisa scale dengan tambah worker |
| Timeout per job | 5 menit | Auto-retry kalau timeout |
| Retry | 3x | Kalau gagal, retry otomatis |

### Estimasi Waktu Async

| Jumlah Jawaban | Estimasi Waktu | Catatan |
|----------------|----------------|---------|
| 20 | 10-30 detik | Cepat, queue kosong |
| 50 | 30-60 detik | Normal |
| 100 | 1-3 menit | Tergantung antrian |
| 500 | 5-10 menit | Banyak, bisa lama |

*Note: Waktu tergantung:
- Jumlah job di queue
- Jumlah worker aktif
- Kompleksitas jawaban*

### Best Practices Async

1. **Selalu gunakan callback webhook**
   - Jangan hanya poll status
   - Webhook lebih real-time

2. **Simpan job_id di database**
   ```php
   JobTracking::create([
       'user_id' => $userId,
       'job_id' => $jobId,
       'status' => 'queued'
   ]);
   ```

3. **Handle retry dengan idempotent**
   - Webhook bisa dipanggil multiple kali (retry)
   - Pastikan handler idempotent (tidak duplikat data)

4. **Scale worker kalau perlu**
   ```bash
   # Tambah worker (4 concurrent)
   celery -A app.celery_app worker --loglevel=info --concurrency=4
   ```

---

## 🔧 Troubleshooting

### Error: "Redis connection refused"
- **Penyebab:** Redis tidak jalan
- **Solusi:** `sudo systemctl start redis-server`

### Error: "Celery worker not running"
- **Penyebab:** Worker belum start
- **Solusi:** `sudo systemctl start celery`

### Job stuck di PENDING lama
- **Penyebab:** Worker tidak ada atau sibuk
- **Solusi:** 
  ```bash
  # Cek worker
  ps aux | grep celery
  
  # Restart worker
  sudo systemctl restart celery
  ```

### Webhook tidak terima callback
- **Penyebab:** URL tidak reachable, timeout, atau firewall
- **Solusi:** 
  - Cek URL bisa diakses dari internet
  - Tambah timeout di webhook handler
  - Cek firewall VPS

### Job FAILED
- **Penyebab:** Error internal (model tidak load, dll)
- **Solusi:** Cek log: `journalctl -u celery -f`

---

## 📊 Flow Diagram

### Sync Flow (Real-time)
```
User ──► Laravel ──► AI Service ──► Process ──► Response ──► User
              │           │            │           │
              │           │            │           └─► Tampil nilai
              │           │            └─► 1-5 detik
              │           └─► HTTP POST
              └─► Submit jawaban
```

### Async Flow (Background)
```
User ──► Laravel ──► AI Service ──► Queue ──► Worker ──► Process
                                              │          │
                                              │          └─► Callback
                                              │              │
                                              │              ▼
                                              │         Laravel Webhook
                                              │              │
                                              │              ▼
                                              │         Update Database
                                              │              │
                                              └─► Poll Status◄┘
                                                    (optional)
```

---

## 📞 Support

Kalau ada kendala:
1. Cek health: `GET /api/v1/health`
2. Cek log AI: `journalctl -u similarity -f`
3. Cek log Worker: `journalctl -u celery -f`
4. Cek Redis: `redis-cli ping`

---

**Versi:** 2.0 (dengan Async Support)  
**Update:** 2024-02-27  
**Author:** AI Similarity Service Team
