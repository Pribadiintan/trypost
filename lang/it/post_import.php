<?php

declare(strict_types=1);

return [
    'title' => 'Importa brief di contenuti',
    'description' => 'Carica un CSV di brief di contenuti (fino a :max righe). Ogni riga diventa una bozza che l\'IA adatta al tuo brand. Genera i post finali più tardi dalla chat.',
    'upload' => 'Carica CSV',

    'empty' => [
        'title' => 'Nessuna importazione',
        'description' => 'Carica un CSV in cui ogni riga è un brief di contenuto.',
    ],

    'status' => [
        'parsing' => 'Analisi',
        'preview_ready' => 'Pronto per la revisione',
        'processing' => 'Elaborazione',
        'completed' => 'Completato',
        'failed' => 'Non riuscito',
    ],

    'row_status' => [
        'valid' => 'Valido',
        'invalid' => 'Non valido',
        'needs_review' => 'Da rivedere',
        'created' => 'Creato',
        'skipped' => 'Saltato',
        'failed' => 'Non riuscito',
    ],

    'summary' => ':valid validi, :invalid saltati',
    'progress' => ':processed su :total elaborati',
    'process' => 'Crea :count bozze',
    'completed' => ':count bozze create ed etichettate come Content Brief.',
    'view_drafts' => 'Vedi bozze',
    'new_import' => 'Nuova importazione',

    'table' => [
        'content' => 'Brief',
        'status' => 'Stato',
        'no_content' => 'Nessun contenuto',
    ],

    'errors' => [
        'upload' => 'Impossibile caricare questo file. Usa un CSV sotto i 5 MB.',
        'process' => 'Impossibile avviare l\'elaborazione. Riprova.',
    ],
];
