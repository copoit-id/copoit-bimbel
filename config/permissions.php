<?php

return [
    'actions' => [
        'view' => 'GET',
        'create' => 'POST',
        'update' => 'PUT',
        'delete' => 'DELETE',
    ],
    'features' => [
        'dashboard' => [
            'label' => 'Dashboard',
            'routes' => ['admin.dashboard'],
        ],
        'profile' => [
            'label' => 'Profil Admin',
            'routes' => ['admin.profile.'],
        ],
        'user_import' => [
            'label' => 'Import User',
            'routes' => ['admin.user.import.'],
        ],
        'user' => [
            'label' => 'Manajemen User',
            'routes' => ['admin.user.'],
        ],
        'akses' => [
            'label' => 'Manajemen Akses',
            'routes' => ['admin.akses.'],
        ],
        'package' => [
            'label' => 'Manajemen Paket',
            'routes' => ['admin.package.'],
        ],
        'question_import' => [
            'label' => 'Import Soal',
            'routes' => ['admin.question-import.'],
        ],
        'question_bank' => [
            'label' => 'Bank Soal',
            'routes' => ['admin.question-bank.'],
        ],
        'question' => [
            'label' => 'Manajemen Soal',
            'routes' => ['admin.question.'],
        ],
        'tryout' => [
            'label' => 'Manajemen Tryout',
            'routes' => ['admin.tryout.'],
        ],
        'feedback' => [
            'label' => 'Feedback Tryout',
            'routes' => ['admin.feedback.'],
        ],
        'class' => [
            'label' => 'Kelas',
            'routes' => ['admin.class.'],
        ],
        'certification' => [
            'label' => 'Sertifikasi',
            'routes' => ['admin.certification.'],
        ],
        'certificate' => [
            'label' => 'Template Sertifikat',
            'routes' => ['admin.certificate.'],
        ],
        'settings' => [
            'label' => 'Pengaturan',
            'routes' => ['admin.settings.'],
        ],
        'leaderboard' => [
            'label' => 'Leaderboard',
            'routes' => ['admin.leaderboard.'],
        ],
        'discussion' => [
            'label' => 'Diskusi',
            'routes' => ['admin.discussion.'],
        ],
        'faq' => [
            'label' => 'FAQ',
            'routes' => ['admin.faq.'],
        ],
        'essay_review' => [
            'label' => 'Koreksi Essay',
            'routes' => ['admin.essay-review.'],
        ],
        'laporan' => [
            'label' => 'Laporan Tryout',
            'routes' => ['admin.laporan.'],
        ],
        'pembayaran' => [
            'label' => 'Pembayaran',
            'routes' => ['admin.pembayaran.'],
        ],
        'tes_koran' => [
            'label' => 'Tes Koran',
            'routes' => ['admin.tes-koran.'],
        ],
        'artikel' => [
            'label' => 'Artikel',
            'routes' => ['admin.artikel.'],
        ],
    ],
];
