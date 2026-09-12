# Blade and frontend

## Blade and forms

- View hanya untuk presentasi; jangan query atau menjalankan business rule kompleks.
- Escape output dengan `{{ }}`; raw HTML hanya untuk konten trusted/sanitized.
- Gunakan component lowercase dengan props jelas dan reuse partial/layout existing.
- Mutation wajib CSRF, validasi server, old input, dan error yang terlihat.
- Tombol submit async perlu disabled/loading untuk mencegah double submit.
- Aksi destruktif perlu confirmation.

## FE harus tipis

- Blade tidak boleh berisi query, transaction, kalkulasi total, permission rule, atau status workflow.
- Hindari deklarasi `@php` kompleks, nested `map/filter/reduce`, closure panjang, dan lookup besar di view.
- Jangan memindahkan business logic ke Alpine/JavaScript hanya karena lebih mudah.
- FE menerima data siap render dari controller/service, misalnya `statusLabel`, `statusClass`, `canApprove`, dan `summary`.
- Conditional di FE boleh untuk presentasi dan UI state, bukan untuk memberi akses.
- Jika data memerlukan keputusan bisnis, tambahkan field/DTO di BE lalu render field tersebut.

## Alpine/Tailwind

- Alpine untuk interaksi ringan, bukan authorization/business logic.
- Hindari listener global berulang dan bersihkan state/event saat komponen ditutup.
- Gunakan utility Tailwind yang konsisten dan responsif.
- Perhatikan focus, keyboard, overlay, modal, z-index, dan stacking context.

## UX states

Fitur interaktif idealnya memiliki initial/loading, success, validation/error, empty, disabled, dan fallback saat JavaScript gagal.
