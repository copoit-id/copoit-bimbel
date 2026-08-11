# Uji Beban Tryout k6

`k6-tryout-simple.js` adalah uji HTTP end-to-end untuk skenario burst. Setiap virtual user memakai satu akun berbeda, login, membuka lobby, memulai tryout, menyimpan jawaban untuk seluruh soal yang dirender, lalu menyelesaikan tryout. Dengan begitu beban yang diuji mencakup session, query tryout, penyimpanan jawaban, perhitungan hasil, dan pembuatan data attempt yang benar-benar dipakai aplikasi.

> Peringatan: skrip ini membuat data `UserAnswer` dan `UserAnswerDetail` sungguhan serta menandai tryout selesai jika `FINISH_TRYOUT` tidak diubah. Jalankan hanya pada tryout dan akun khusus pengujian. Pastikan limit attempt cukup atau tidak dibatasi.

## Menyiapkan akun

Salin template lalu isi akun peserta khusus uji. File asli sengaja tidak disediakan dan diabaikan Git agar password tidak masuk repository.

```bash
cp tests/load/users.example.csv tests/load/users.csv
```

Formatnya `email,password`, satu akun unik per baris. Jumlah akun harus minimal sama dengan `VUS`. Semua akun harus dapat mengakses package/tryout yang diuji; untuk package berbayar, berikan akses package terlebih dahulu.

## Menjalankan burst 324 peserta dan 40 soal

Perintah berikut melepas 324 peserta bersamaan. Nilai `ANSWER_INTERVAL_SECONDS=0` sengaja merupakan kondisi terberat: setelah halaman tryout dibuka, seluruh peserta mengirim simpan jawaban tanpa jeda. `QUESTION_COUNT=40` adalah penjaga supaya pengujian langsung gagal jika ternyata halaman tidak memuat tepat 40 soal.

```bash
k6 run \
  -e BASE_URL=http://127.0.0.1:8000 \
  -e PACKAGE_ID=free \
  -e TRYOUT_ID=8 \
  -e VUS=324 \
  -e QUESTION_COUNT=40 \
  -e ANSWER_INTERVAL_SECONDS=0 \
  tests/load/k6-tryout-simple.js
```

Jumlah VU dinamis: ubah `-e VUS=324`. Bila `VUS` tidak diisi, skrip memakai seluruh baris akun pada `users.csv`. Jika jumlah akun kurang, skrip berhenti sebelum mengirim request apa pun.

## Parameter penting

| Parameter | Default | Kegunaan |
| --- | --- | --- |
| `BASE_URL` | `http://127.0.0.1:8000` | Server tujuan. |
| `PACKAGE_ID` | `free` | ID package atau `free`. |
| `TRYOUT_ID` | wajib | ID tryout yang diuji. |
| `USERS_FILE` | `./users.csv` | Lokasi CSV akun uji relatif terhadap skrip. |
| `VUS` | jumlah akun CSV | Jumlah peserta serentak. |
| `QUESTION_COUNT` | seluruh soal | Jika diisi, harus sama persis dengan jumlah soal yang dirender. |
| `ANSWER_INTERVAL_SECONDS` | `0` | Jeda antar jawaban setiap peserta. Gunakan `0.5` atau `1` untuk pola manusiawi. |
| `FINISH_TRYOUT` | `true` | Set `false` untuk hanya menguji simpan jawaban tanpa proses hasil akhir. |
| `MAX_DURATION` | `20m` | Batas total waktu skenario. |

Skrip memilih opsi jawaban pertama yang tersedia; targetnya adalah beban dan alur penyimpanan, bukan nilai yang benar. Soal pilihan ganda, multiple answer, matching, multiple true/false, jawaban singkat, dan essay ditangani. Soal audio sengaja membuat test gagal karena upload audio harus memakai file rekaman yang valid dan tidak boleh dipalsukan sebagai jawaban lengkap.

Untuk tryout yang memiliki essay dengan koreksi AI, menjalankan `FINISH_TRYOUT=true` dapat menambah antrean koreksi. Gunakan tryout tanpa essay untuk baseline platform, atau jadikan antrean AI tersebut bagian dari skenario yang memang ingin diuji.
