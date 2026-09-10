<?php

declare(strict_types=1);

return [
    'title' => 'İçerik brief\'lerini içe aktar',
    'description' => 'İçerik brief\'lerinden oluşan bir CSV yükleyin (en fazla :max satır). Her satır, yapay zekânın markanıza göre düzenlediği bir taslağa dönüşür. Son gönderileri daha sonra sohbetten oluşturun.',
    'upload' => 'CSV yükle',

    'empty' => [
        'title' => 'Henüz içe aktarma yok',
        'description' => 'Her satırın bir içerik brief\'i olduğu bir CSV yükleyin.',
    ],

    'status' => [
        'parsing' => 'Ayrıştırılıyor',
        'preview_ready' => 'İncelemeye hazır',
        'processing' => 'İşleniyor',
        'completed' => 'Tamamlandı',
        'failed' => 'Başarısız',
    ],

    'row_status' => [
        'valid' => 'Geçerli',
        'invalid' => 'Geçersiz',
        'needs_review' => 'İnceleme gerekli',
        'created' => 'Oluşturuldu',
        'skipped' => 'Atlandı',
        'failed' => 'Başarısız',
    ],

    'summary' => ':valid geçerli, :invalid atlandı',
    'progress' => ':total içinden :processed işlendi',
    'process' => ':count taslak oluştur',
    'completed' => ':count taslak oluşturuldu ve Content Brief olarak etiketlendi.',
    'view_drafts' => 'Taslakları gör',
    'new_import' => 'Yeni içe aktarma',

    'table' => [
        'content' => 'Brief',
        'status' => 'Durum',
        'no_content' => 'İçerik yok',
    ],

    'errors' => [
        'upload' => 'Bu dosya yüklenemedi. 5 MB altında bir CSV kullanın.',
        'process' => 'İşleme başlatılamadı. Tekrar deneyin.',
    ],
];
