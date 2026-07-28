<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Module groups
    |--------------------------------------------------------------------------
    |
    | Feature keys reuse the permission feature keys whenever possible. Keys
    | without an admin permission are allowed for user-facing modules.
    |
    */
    'groups' => [
        'core' => [
            'label' => 'Fitur Inti',
            'description' => 'Fitur dasar yang selalu tersedia pada seluruh preset.',
            'features' => [
                'dashboard',
                'profile',
                'user_import',
                'user',
                'akses',
                'settings',
                'activity',
                'update_notification',
            ],
        ],
        'cbt' => [
            'label' => 'CAT / CBT',
            'description' => 'Paket ujian, bank soal, tryout, hasil, dan fitur evaluasi.',
            'features' => [
                'package',
                'question_import',
                'ai_question_generator',
                'question_bank',
                'question',
                'tryout',
                'feedback',
                'certification',
                'certificate',
                'leaderboard',
                'essay_review',
                'laporan',
                'tes_koran',
                'event',
            ],
        ],
        'administration' => [
            'label' => 'Administrasi',
            'description' => 'Kelas, tutor, absensi, tagihan, pembayaran, dan keuangan.',
            'features' => [
                'class',
                'tentor',
                'tutor_payroll',
                'pembayaran',
                'finance',
                'recurring_bill',
                'discount',
            ],
        ],
        'extended' => [
            'label' => 'Fitur Tambahan',
            'description' => 'Materi belajar, komunikasi, affiliate, dan pengelolaan halaman publik.',
            'features' => [
                'material_category',
                'material',
                'discussion',
                'faq',
                'affiliate',
                'artikel',
                'general_page',
                'ai_learning',
            ],
        ],
    ],

    'presets' => [
        'cbt_only' => [
            'label' => 'CAT / CBT Only',
            'description' => 'Fitur inti dan seluruh kebutuhan CAT/CBT.',
            'groups' => ['core', 'cbt'],
        ],
        'administration_only' => [
            'label' => 'Administrasi Only',
            'description' => 'Fitur inti dan seluruh kebutuhan administrasi bimbel.',
            'groups' => ['core', 'administration'],
        ],
        'standard' => [
            'label' => 'Standar (CBT + Administrasi)',
            'description' => 'Gabungan modul CAT/CBT dan administrasi.',
            'groups' => ['core', 'cbt', 'administration'],
        ],
        'full' => [
            'label' => 'Full Fitur',
            'description' => 'Seluruh fitur aplikasi aktif.',
            'groups' => ['core', 'cbt', 'administration', 'extended'],
        ],
        'custom' => [
            'label' => 'Kustom',
            'description' => 'Pilihan fitur telah disesuaikan secara manual.',
            'groups' => [],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Additional route mappings
    |--------------------------------------------------------------------------
    |
    | Admin route prefixes remain sourced from config/permissions.php. These
    | mappings add user-facing routes and features without admin permissions.
    | The most-specific matching prefix wins.
    |
    */
    'routes' => [
        'dashboard' => ['user.dashboard.'],
        'profile' => ['user.profile.'],
        'user' => [
            'admin.participant-destination-categories.',
            'participant-destinations.',
        ],
        'package' => [
            'user.package.',
            'user.individual-purchase.',
        ],
        'certification' => [
            'user.package.sertifikasi',
        ],
        'ai_question_generator' => ['admin.question-generator.quota.'],
        'tryout' => [
            'user.tryout.',
            'user.package.tryout',
            'admin.package.tryout.',
        ],
        'material' => [
            'user.material.',
            'admin.package.material.',
        ],
        'class' => [
            'admin.class-sessions.',
            'admin.package.class.',
            'tutor.schedule.',
            'tutor.attendance.',
            'user.class.',
            'user.class-schedule.',
        ],
        'discussion' => [
            'tutor.chat.',
            'user.chat.',
        ],
        'pembayaran' => ['user.billing.'],
        'tes_koran' => [
            'admin.package.tes-koran.',
            'user.tes-koran.',
            'user.tes-kecermatan.',
        ],
        'certificate' => ['user.certificate.'],
        'faq' => ['user.help.'],
        'affiliate' => ['user.affiliate.'],
        'event' => ['user.event.'],
        'ai_learning' => [
            'admin.assistant.',
            'user.ai-learning.',
            'user.ai-gateway.',
        ],
        'laporan' => ['laporan.live-score.'],
        'artikel' => [
            'articles.',
            'general.articles.',
            'general.blog.',
        ],
        'general_page' => [
            'general.',
            'statistics',
        ],
    ],

    'labels' => [
        'event' => 'Event Gratis',
        'ai_learning' => 'AI Learning Tools',
    ],
];
