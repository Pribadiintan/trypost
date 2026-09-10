<?php

declare(strict_types=1);

return [
    'title' => 'Import ringkasan kandungan',
    'description' => 'Muat naik CSV ringkasan kandungan (sehingga :max baris). Setiap baris menjadi draf yang diperhalusi AI mengikut jenama anda. Jana pos akhir kemudian daripada sembang.',
    'upload' => 'Muat naik CSV',

    'empty' => [
        'title' => 'Belum ada import',
        'description' => 'Muat naik CSV yang setiap barisnya satu ringkasan kandungan.',
    ],

    'status' => [
        'parsing' => 'Menghurai',
        'preview_ready' => 'Sedia disemak',
        'processing' => 'Memproses',
        'completed' => 'Selesai',
        'failed' => 'Gagal',
    ],

    'row_status' => [
        'valid' => 'Sah',
        'invalid' => 'Tidak sah',
        'needs_review' => 'Perlu semakan',
        'created' => 'Dicipta',
        'skipped' => 'Dilangkau',
        'failed' => 'Gagal',
    ],

    'summary' => ':valid sah, :invalid dilangkau',
    'progress' => ':processed daripada :total diproses',
    'process' => 'Cipta :count draf',
    'completed' => ':count draf dicipta dan dilabel Content Brief.',
    'view_drafts' => 'Lihat draf',
    'new_import' => 'Import baharu',

    'table' => [
        'content' => 'Ringkasan',
        'status' => 'Status',
        'no_content' => 'Tiada kandungan',
    ],

    'errors' => [
        'upload' => 'Tidak dapat memuat naik fail ini. Guna CSV bawah 5 MB.',
        'process' => 'Tidak dapat memulakan pemprosesan. Cuba lagi.',
    ],
];
