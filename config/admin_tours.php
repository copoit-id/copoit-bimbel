<?php

return [
    'admin.tryout.create' => [
        'key' => 'admin.tryout.create',
        'version' => 3,
        'title' => 'Membuat Tryout',
        'portal' => ['admin', 'tutor'],
        'required_permission' => ['feature' => 'tryout', 'action' => 'create'],
        'steps' => [
            ['id' => 'open_create', 'route' => 'admin.tryout.index', 'target' => '[data-tour="tryout.create"]', 'type' => 'click_target', 'title' => 'Buat tryout baru', 'body' => 'Klik Tambah Tryout untuk membuka form pembuatan.', 'allowed_action' => 'click', 'next_route' => 'admin.tryout.create'],
            ['id' => 'fill_name', 'route' => 'admin.tryout.create', 'target' => '[data-tour="tryout.name"]', 'type' => 'input_target', 'title' => 'Isi nama tryout', 'body' => 'Masukkan nama yang mudah dikenali peserta.', 'allowed_action' => 'input'],
            ['id' => 'set_schedule', 'route' => 'admin.tryout.create', 'target' => '#start_date', 'type' => 'explain', 'title' => 'Atur periode tryout', 'body' => 'Tentukan tanggal mulai. Tanggal selesai diatur pada kolom di sebelahnya.', 'allowed_action' => 'none'],
            ['id' => 'complete', 'route' => 'admin.tryout.create', 'target' => '#tryout-submit', 'type' => 'complete', 'title' => 'Anda siap melanjutkan', 'body' => 'Lengkapi pengaturan lain, lalu simpan tryout ketika sudah siap.', 'allowed_action' => 'none'],
        ],
    ],
];
