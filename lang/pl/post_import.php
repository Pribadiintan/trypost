<?php

declare(strict_types=1);

return [
    'title' => 'Importuj brief-y treści',
    'description' => 'Prześlij plik CSV z brief-ami treści (do :max wierszy). Każdy wiersz staje się szkicem dopasowanym przez AI do Twojej marki. Wygeneruj finalne posty później z czatu.',
    'upload' => 'Prześlij CSV',

    'empty' => [
        'title' => 'Brak importu',
        'description' => 'Prześlij plik CSV, w którym każdy wiersz to jeden brief treści.',
    ],

    'status' => [
        'parsing' => 'Przetwarzanie',
        'preview_ready' => 'Gotowe do przeglądu',
        'processing' => 'Przetwarzanie',
        'completed' => 'Zakończono',
        'failed' => 'Niepowodzenie',
    ],

    'row_status' => [
        'valid' => 'Prawidłowy',
        'invalid' => 'Nieprawidłowy',
        'needs_review' => 'Wymaga przeglądu',
        'created' => 'Utworzono',
        'skipped' => 'Pominięto',
        'failed' => 'Niepowodzenie',
    ],

    'summary' => ':valid prawidłowych, :invalid pominiętych',
    'progress' => 'Przetworzono :processed z :total',
    'process' => 'Utwórz :count szkiców',
    'completed' => 'Utworzono :count szkiców z etykietą Content Brief.',
    'view_drafts' => 'Zobacz szkice',
    'new_import' => 'Nowy import',

    'table' => [
        'content' => 'Brief',
        'status' => 'Status',
        'no_content' => 'Brak treści',
    ],

    'errors' => [
        'upload' => 'Nie udało się przesłać pliku. Użyj pliku CSV poniżej 5 MB.',
        'process' => 'Nie udało się rozpocząć przetwarzania. Spróbuj ponownie.',
    ],
];
