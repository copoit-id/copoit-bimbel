# Architecture, structure, and naming

## Filesystem

- Selalu lowercase; gunakan kebab-case untuk folder view/component multi-kata.
- Anggap filesystem production case-sensitive.
- Jangan membuat dua file yang hanya berbeda kapitalisasi.

Contoh: `resources/views/components/ui/button/index.blade.php` dan `<x-ui.button>`.

## Layering

- Route: endpoint, middleware, name, dan binding.
- Controller: request, validasi, service, response.
- Service/action: business rule dan transaction.
- Model: relasi, cast, fillable, scope, query concern sederhana.
- View: presentasi; jangan menjalankan query atau aturan bisnis.

## Backend-first logic boundary

- BE/service adalah sumber kebenaran untuk business rule.
- Controller menyiapkan data siap tampil; transformasi kompleks berada di service/presenter/view model.
- FE hanya merender nilai dari BE dan mengelola state UI lokal seperti modal, tab, loading, dan input sementara.
- FE tidak boleh menentukan harga, kuota, status pembayaran, akses user, hasil scoring, atau authorization.
- Jangan membuat query, kalkulasi bisnis, nested mapping panjang, permission check, atau workflow di Blade.
- Jangan menduplikasi aturan yang sama di PHP dan JavaScript. Validasi UX di FE tidak menggantikan validasi BE.

```php
// Controller/service menyiapkan data final
return view('orders.index', [
    'paidTotal' => $orderSummary->paidTotal,
    'canEdit' => $authorization->canEdit($item),
]);
```

```blade
<span>{{ $paidTotal }}</span>
@if($canEdit) ... @endif
```

Ikuti struktur existing sebelum membuat namespace/folder baru. Pertahankan backward compatibility untuk URL/data existing kecuali diminta lain.

## Naming

- Class `PascalCase`; method/variable `camelCase`.
- Database table/column dan route parameter mengikuti konvensi existing.
- Route name memakai prefix konsisten, misalnya `admin.packages.index`.
- Boolean gunakan nama jelas seperti `is_active`, `is_enabled`, atau `has_access`.

Sebelum edit, cari pemakaian simbol/route/view dengan `rg`.
