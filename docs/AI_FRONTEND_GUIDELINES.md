# AI Frontend Guidelines

Pedoman ini wajib diikuti AI/developer saat mengubah antarmuka BimbelHub. Tujuannya menjaga UI tetap konsisten, mudah dipahami, dan dapat dipelihara.

## 1. Keterangan field

- Jika sebuah field membutuhkan penjelasan tambahan, gunakan `<x-ui.tooltip>` dengan ikon informasi di samping label.
- Tooltip menjelaskan **mengapa** atau dampak pilihan field tersebut, bukan mengulang label.
- Jangan menambahkan paragraf bantuan permanen di bawah field bila informasi cukup dijelaskan melalui tooltip. Helper text tetap dipakai hanya bila pengguna perlu membacanya terus-menerus saat mengisi.
- Pesan validasi/error tetap tampil di dekat field dan tidak boleh dipindahkan ke tooltip.

```blade
<label class="flex items-center text-sm font-medium text-gray-900">
    Siklus Tagihan
    <x-ui.tooltip>Pilih Mingguan agar seluruh pertemuan Senin–Minggu memakai satu tagihan.</x-ui.tooltip>
</label>
```

## 2. Component-first

Sebelum menulis markup, class Tailwind, atau JavaScript baru:

1. Cari komponen yang relevan dengan `rg --files resources/views/components` dan `rg` pada pemakaiannya.
2. Gunakan atau perluas komponen yang ada jika tanggung jawabnya sama.
3. Jika belum ada, buat komponen dasar reusable di `resources/views/components/` dengan nama lowercase/kebab-case, props yang jelas, serta dokumentasi singkat di file komponen.
4. Setelah komponen tersedia, gunakan di seluruh permukaan yang terdampak. Jangan membuat variasi markup per halaman untuk pola UI yang sama.

Komponen dasar yang perlu diprioritaskan: `x-ui.input`, `x-ui.input.select`, `x-ui.button`, `x-ui.tooltip`, `x-tab`, `x-ui.modal`, dan `x-ui.card`.

## 3. Standar UX dan visual

- Ikuti komponen, warna, radius, ukuran teks, dan state interaksi yang sudah digunakan aplikasi; jangan membuat visual system baru per halaman.
- Gunakan label yang ringkas, hierarchy yang jelas, empty/error/loading state yang relevan, serta area klik yang nyaman di desktop dan mobile.
- Tab, filter, dan kontrol berulang wajib memakai komponen bersama agar active, hover, focus, keyboard, dan overflow mobile konsisten.
- Untuk tabel yang dapat digeser horizontal, kolom aksi harus dibuat sticky di sisi kanan agar aksi utama tetap dapat dijangkau. Pastikan sel sticky memiliki background dan border sendiri agar isi kolom lain tidak terlihat menembusnya saat digeser.
- UI hanya menangani presentasi dan state lokal. Harga, status, izin, perhitungan, dan keputusan bisnis tetap berasal dari backend.
- Sebelum handoff, cek responsif, fokus keyboard, teks panjang, empty state, dan konsistensi dengan halaman sejenis.

## 4. Validasi perubahan UI

- Jalankan Blade cache dan `git diff --check`.
- Jalankan build atau validasi Vite yang tidak menimpa perubahan pengguna.
- Jika browser tersedia, verifikasi interaksi visual pada halaman yang diubah. Jika tidak tersedia, laporkan batasan tersebut secara jujur.
