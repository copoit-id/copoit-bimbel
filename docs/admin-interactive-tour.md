# Rancangan Tutor Navigasi Interaktif untuk Semua Menu Admin

## Tujuan

Membuat **Tutor Navigasi**: panduan interaktif seperti tutorial pada aplikasi/game. Di setiap halaman fitur yang memiliki tour, admin melihat tombol ikon bantuan di samping judul halaman. Saat dipilih, aplikasi:

1. meredupkan halaman;
2. menyorot satu elemen yang harus diperhatikan/diklik;
3. menjelaskan tindakan dalam kartu instruksi; dan
4. mengunci interaksi selain elemen yang diizinkan sampai langkah selesai, dilewati, atau tour ditutup.

Contoh alur: pada halaman daftar Tryout, tutor menyorot tombol **Tambah Tryout**, meminta admin menekannya, pindah ke halaman form, lalu menjelaskan field penting dan tombol simpan.

Nama UI adalah **Tutor Navigasi**. Jangan memakai `tutor` sebagai nama route/model utama karena project sudah memakai istilah *tutor* untuk peran pengguna. Gunakan `admin-tour`/`interactive-tour` pada kode dan route.

## Batasan produk

- Cakupan target: seluruh menu admin dan super admin yang aktif, termasuk halaman daftar, detail, buat, edit, dan halaman pengaturan yang memiliki alur bermakna.
- Tour hanya ditampilkan bila halaman, feature flag, dan permission pemohon mengizinkannya.
- Fase pertama adalah panduan navigasi dan penggunaan UI; tidak menjalankan aksi bisnis secara otomatis.
- Admin tetap dapat menutup atau melewati tour kapan saja, kecuali suatu tour secara eksplisit ditetapkan wajib pada masa onboarding. Default-nya selalu dapat ditutup.
- Jangan membuat satu tour raksasa untuk semua menu. Satu tour harus fokus pada satu tujuan, maksimal sekitar 5–8 langkah.

## Kondisi saat ini

Project sudah memiliki onboarding khusus AI Learning pada halaman user, tetapi belum memiliki engine tour generik untuk admin. Karena itu implementasi baru harus menjadi komponen bersama, bukan menyalin JavaScript tutorial ke setiap Blade.

## Pengalaman pengguna

### Tombol pemicu

Tombol muncul di samping judul utama halaman bila tersedia tour yang dapat diakses:

```text
Tryout                                      [? Pelajari halaman ini]
Kelola tryout, subtest, dan soal.
```

Di layar kecil tombol dapat menjadi icon-only dengan `aria-label="Mulai tutor navigasi"`. Tooltip tetap menjelaskan tujuannya.

### Tampilan ketika aktif

```text
┌──────────────────────────────── halaman diredupkan ───────────────────────────┐
│                                                                                │
│   [Sidebar dan konten tidak dapat diklik]                                     │
│                                                                                │
│                                      ╭────────────────────────────────────╮  │
│                                      │ Langkah 1 dari 4                    │  │
│      ┌───────────────────────┐       │ Tambahkan tryout baru                │  │
│      │ + Tambah Tryout       │◀──────│ Klik tombol ini untuk mulai.         │  │
│      └───────────────────────┘       │ [Lewati] [Tutup]                     │  │
│          elemen disorot dan aktif    ╰────────────────────────────────────╯  │
│                                                                                │
└────────────────────────────────────────────────────────────────────────────────┘
```

Kartu instruksi mengikuti posisi target bila ruang memungkinkan; bila tidak, diposisikan aman dalam viewport. Fokus keyboard selalu masuk ke kartu/target yang aktif. Tombol `Esc` menutup tour setelah konfirmasi ringan bila progres belum selesai.

### Jenis langkah

| Jenis | Perilaku | Contoh |
|---|---|---|
| `explain` | Menyorot elemen, user menekan **Lanjut** | Menjelaskan filter status |
| `click_target` | Hanya target yang dapat diklik; event klik menyelesaikan langkah | Tombol Tambah Tryout |
| `input_target` | Hanya field/form yang diizinkan aktif; validasi lokal memastikan nilai terisi | Nama tryout |
| `form_submit` | Menunggu submit sukses; tidak mengirim sendiri | Simpan draft |
| `navigate` | Meneruskan tour ke route berikutnya setelah CTA yang disetujui | Daftar -> halaman buat |
| `complete` | Menampilkan ringkasan dan CTA kembali ke fitur | Tour selesai |

Untuk aksi berisiko (hapus, refund, revoke akses, approval pembayaran, publish massal), tutor hanya menjelaskan dan tidak pernah mengunci user agar melakukan aksi tersebut. Target tersebut tidak boleh menjadi `click_target` atau `form_submit` wajib.

## Arsitektur

```text
Blade page
  ├─ <x-admin.tour-button tour-key="admin.tryout.create" />
  └─ marker data-tour="tryout.create"
        │
        ▼
Tour registry (server-side, versioned)
  ├─ cek route, feature flag, permission, dan kondisi data
  ├─ mengirim hanya langkah yang boleh dilihat user
  └─ memilih entry step untuk route saat ini
        │
        ▼
<x-admin.interactive-tour /> (satu kali di layout)
  ├─ overlay + spotlight + instruction card
  ├─ focus trap, keyboard/accessibility, scroll/reposition
  ├─ action gate / event listener
  └─ menyimpan progres ke API
        │
        ▼
AdminTourProgressService + audit ringan
```

Gunakan komponen Blade yang seluruh foldernya lowercase sesuai aturan project:

```text
resources/views/components/admin/
├── interactive-tour/
│   └── index.blade.php
└── tour-button/
    └── index.blade.php
```

Engine JavaScript cukup satu module/komponen Alpine terpusat. Jangan memakai inline script berbeda pada setiap halaman. Library pihak ketiga boleh dievaluasi kemudian, tetapi MVP sebaiknya memakai implementasi sendiri yang ringan karena behavior lock, route transition, dan permission perlu dikendalikan penuh.

## Kontrak definisi tour

Definisi disimpan di PHP, misalnya `app/Support/AdminTours/`, bukan di database bebas dan bukan ditulis oleh LLM. Setiap file/kelas menyatakan key, version, audience, permission, kondisi feature, dan langkah.

```php
return [
    'key' => 'admin.tryout.create',
    'version' => 1,
    'title' => 'Membuat Tryout',
    'portal' => ['admin'],
    'required_permissions' => ['tryout.create'],
    'feature' => 'tryout',
    'steps' => [
        [
            'id' => 'open_create',
            'route' => 'admin.tryout.index',
            'target' => '[data-tour="tryout.create"]',
            'type' => 'click_target',
            'title' => 'Buat tryout baru',
            'body' => 'Klik Tambah Tryout untuk membuka form pembuatan.',
            'allowed_action' => 'click',
            'next_route' => 'admin.tryout.create',
        ],
    ],
];
```

Aturan wajib untuk definisi:

- `target` harus berupa selector `data-tour` unik, bukan class Tailwind atau struktur DOM yang mudah berubah.
- Semua `route` harus route name yang valid; URL dibuat server-side.
- `required_permissions` dan `feature` diperiksa server-side sebelum definition dikirim.
- `version` dinaikkan bila alur/selector berubah secara material; progress version lama dianggap perlu diulang.
- Tidak ada HTML mentah dari database/user pada `title` atau `body`.
- `allowed_action` hanya menerima allowlist (`click`, `input`, `submit`, `navigate`, `none`).
- Definition tidak boleh menargetkan elemen yang tidak ada; ini diuji otomatis.

## Penguncian interaksi yang aman

Kebutuhan “tidak bisa ngapa-ngapain” diterapkan hanya selama satu langkah aktif:

1. overlay menangkap pointer event pada seluruh halaman;
2. spotlight dipotong pada target aktif, dan target diberi `pointer-events: auto` hanya bila langkah membutuhkan aksi;
3. sidebar, navbar, hotkey, form lain, dan link lain diblok;
4. `Tab` dikunci di kartu instruksi dan target aktif; `Escape` menjalankan alur tutup;
5. saat target hilang akibat responsive layout/feature flag/perubahan data, tour berhenti aman dengan pesan “Halaman berubah, mulai ulang tutor.”

Jangan mengandalkan `pointer-events` saja. Tambahkan event guard pada capture phase untuk klik, submit, dan shortcut, serta restore semua listener/style saat tour selesai agar halaman tidak terkunci.

Untuk langkah `input_target`, pengguna hanya dapat berinteraksi dengan field/form yang diizinkan. Namun form tetap memakai validasi Laravel normal—engine tour bukan sumber validasi bisnis.

## Kelanjutan antar halaman

Tour harus bertahan ketika admin berpindah dari index ke create/edit/detail:

1. sebelum navigasi, browser menyimpan `tour_key`, `version`, `step_id`, dan timestamp di session storage;
2. request berikutnya memuat progress dari server untuk user yang sama;
3. layout memulai kembali engine hanya bila route/permission/feature tetap valid;
4. setelah action sukses, backend atau event UI menandai langkah selesai dan mengarahkan ke langkah berikutnya;
5. bila halaman kembali dengan error validasi, tour tetap pada langkah form dan menjelaskan error tanpa menyembunyikan flash message.

Session storage hanya untuk kelancaran navigasi; tabel progress server adalah sumber kebenaran untuk status selesai/skip. Jangan memasukkan role, route yang diizinkan, atau state authorization dari browser.

## Data model dan endpoint

### Tabel `admin_tour_progress`

| Kolom | Keterangan |
|---|---|
| `admin_tour_progress_id` | Primary key |
| `user_id` | Admin yang menjalankan tour |
| `tour_key` | Key stabil, mis. `admin.tryout.create` |
| `tour_version` | Versi definition yang sedang dikerjakan |
| `status` | `in_progress`, `completed`, `skipped`, `dismissed` |
| `current_step_id` | Langkah terakhir untuk resume |
| `completed_at`, `skipped_at` | Timestamp nullable |
| `metadata` | JSON kecil untuk telemetry aman, tanpa data form/PII |
| timestamps | Audit dasar |

Gunakan unique index `(user_id, tour_key, tour_version)` dan index untuk `status`/`updated_at`. Migration wajib production-safe: cek tabel/kolom/index yang relevan sebelum menambah, dan jangan menghapus progress versi lama saat rollout.

### Route internal

Semua route berada dalam group admin yang sudah memiliki `auth`, `AdminMiddleware`, `permission`, dan `no-cache`:

| Method | Route | Fungsi |
|---|---|---|
| `GET` | `/{portal}/tours/{tourKey}` | Mengambil definition terfilter untuk halaman aktif |
| `POST` | `/{portal}/tours/{tourKey}/start` | Memulai/resume progress |
| `POST` | `/{portal}/tours/{tourKey}/steps/{stepId}` | Catat langkah selesai/skip/dismiss; server validasi urutan |
| `POST` | `/{portal}/tours/{tourKey}/complete` | Menutup tour sebagai selesai setelah seluruh langkah valid |

Controller tidak boleh menerima `user_id`, `permission`, route target, atau arbitrary selector dari browser. Semuanya dihitung dari session admin dan registry.

## Permission, feature flag, dan keamanan

- Tambahkan permission baru `admin_tour.use` untuk membuka tutor. Definisi tour masih memiliki permission fitur masing-masing.
- Super admin dapat mengaktifkan flag global `admin_tours_enabled`; optional override per feature menyusul bila perlu.
- Jangan tampilkan CTA/tour untuk tutor/admin yang tidak berhak mengakses target; route transition perlu dicek ulang di setiap request.
- Kunci tindakan berisiko tinggi: delete, publish irreversible, akses finansial, reset password, impersonation, ekspor PII, revoke/subscription/payment approval.
- Tidak ada data form yang disimpan dalam event progress; catat hanya `tour_key`, `step_id`, status, timestamp, route, dan error category.
- Escape hatch harus selalu ada untuk accessibility dan recovery; penguncian UI bukan kontrol keamanan. Otorisasi tetap dilakukan oleh middleware/controller.

## Aksesibilitas dan responsif

- Overlay mempunyai `role="dialog"`, `aria-modal="true"`, judul/deskripsi terhubung dengan `aria-labelledby` dan `aria-describedby`.
- Fokus berpindah ke kartu instruksi ketika langkah berubah; fokus semula dikembalikan saat ditutup.
- Tombol selalu punya label jelas: **Lanjut**, **Kembali**, **Lewati tour**, **Tutup**.
- Jangan hanya mengandalkan warna/animasi. Spotlight harus memiliki border/high-contrast dan instruksi teks.
- Hormati `prefers-reduced-motion`; animasi spotlight/pulse dimatikan atau dipercepat.
- Pada layar kecil, kartu menjadi bottom sheet dan memastikan target tidak tertutup; scroll target memakai offset navbar.
- Semua flow inti dapat selesai dengan keyboard tanpa mouse.

## Strategi untuk “semua fitur”

Tidak realistis atau aman menulis tour untuk seluruh halaman sekaligus tanpa standardisasi. Gunakan inventaris dan rollout bertingkat berikut.

| Prioritas | Area | Tour pertama |
|---|---|---|
| P0 | Dashboard, Paket, Materi, Tryout, Bank Soal, User | orientasi daftar dan membuat entitas baru |
| P1 | Pembayaran, Akses Paket, Kelas, Kehadiran, Diskusi, Laporan/Leaderboard | alur operasional umum dan filter/hasil |
| P2 | Discount, Event, Tes Koran, Study Group, Affiliate, Booking, Sertifikat | alur per fitur yang telah distabilkan |
| P3 | Semua menu super admin: admin/role, plan, setting, AI gateway, billing | panduan dengan perhatian khusus pada aksi sensitif |

Setiap halaman yang masuk rollout wajib memenuhi checklist:

1. judul halaman memakai/menyediakan slot `tour-button`;
2. action utama mempunyai `data-tour` yang unik dan stabil;
3. permission + feature flag dicatat pada registry;
4. alur sudah direview pemilik fitur;
5. test selector dan authorization ditambahkan; dan
6. versi tour serta changelog diperbarui.

Halaman yang hanya bersifat read-only tetap dapat mempunyai tour `explain`, misalnya menjelaskan kartu statistik, filter, atau cara membaca status. Halaman tanpa aksi tidak dipaksa memiliki tour.

## Telemetry, evaluasi, dan kualitas

Ukur tanpa menyimpan data sensitif:

- start, step viewed, step completed, skipped, dismissed, dan completed;
- durasi per langkah dan titik paling sering ditinggalkan;
- selector not found, route mismatch, blocked action, serta error submit;
- feedback singkat setelah selesai: “Panduan ini membantu?”

Dashboard internal perlu menampilkan completion rate per tour/version. Jika selector missing atau completion rate turun drastis setelah deploy, nonaktifkan definition tersebut via flag sampai diperbaiki.

## Pengujian wajib

1. **Unit:** registry memfilter portal, permission, feature flag, version, dan urutan langkah dengan benar.
2. **Feature:** endpoint tidak bisa membaca/mengubah progress user lain; target tidak berizin tidak pernah dikirim.
3. **Browser/E2E:** overlay memblok action di luar target, target bisa diklik, progress lolos antar route, Esc/tutup memulihkan UI, dan mobile layout tidak menutup target.
4. **Regression selector:** setiap selector `data-tour` pada definition ditemukan pada Blade/DOM halaman target.
5. **Accessibility:** keyboard navigation, focus trap, screen-reader label, reduced motion.
6. **Manual UAT:** pemilik fitur menjalankan seluruh tour di admin dan super admin dengan akun yang permission-nya berbeda.

## Tahapan implementasi

### Fase 1 — Engine dan satu alur referensi

1. Tambah flag, permission, data model progress, registry, endpoint, dan dua Blade component bersama.
2. Implementasi engine overlay/spotlight/lock/focus/reposition/resume route.
3. Buat tour referensi **Tryout: dari daftar sampai buka form create**. Tidak perlu sampai menyimpan tryout pada fase awal.
4. Tambah test unit, feature, dan E2E untuk flow tersebut.

### Fase 2 — Standardisasi halaman

1. Tambah slot tombol tutor pada page header/layout yang konsisten.
2. Tambah `data-tour` pada action utama P0.
3. Tulis definition tour P0 dan lakukan UAT per role.
4. Aktifkan untuk internal/super admin lebih dulu dan pantau telemetry.

### Fase 3 — Perluasan dan pemeliharaan

1. Perluas P1–P3 bertahap berdasarkan menu yang paling sering dipakai.
2. Jadikan update definition/selector/tour version sebagai checklist wajib setiap PR yang mengubah UI.
3. Tambahkan link dari Tutor Navigasi ke Asisten AI Admin untuk pertanyaan lanjutan, tetapi keduanya tetap fitur terpisah.

## Kriteria selesai MVP

- Tombol Tutor Navigasi muncul hanya di halaman tour yang valid dan hanya untuk pengguna berizin.
- Tour Tryout berjalan lintas halaman, menyorot target tepat, dan interaksi di luar target benar-benar diblok saat langkah aktif.
- Admin dapat skip/tutup dan halaman selalu pulih normal.
- Progress aman per user dan versi, tidak dapat dimanipulasi untuk mengakses menu lain.
- Test authorization, selector, E2E, mobile, dan accessibility lulus.
- Tidak ada action bisnis berisiko yang dipicu/dipaksa oleh tour.
