<?php

declare(strict_types=1);

return [
    'title' => 'Importar briefings de conteúdo',
    'description' => 'Envie um CSV de briefings de conteúdo (até :max linhas). Cada linha vira um rascunho que a IA ajusta à sua marca. Gere as publicações finais depois pelo chat.',
    'upload' => 'Enviar CSV',

    'empty' => [
        'title' => 'Nenhuma importação ainda',
        'description' => 'Envie um CSV em que cada linha é um briefing de conteúdo.',
    ],

    'status' => [
        'parsing' => 'Analisando',
        'preview_ready' => 'Pronto para revisar',
        'processing' => 'Processando',
        'completed' => 'Concluído',
        'failed' => 'Falhou',
    ],

    'row_status' => [
        'valid' => 'Válido',
        'invalid' => 'Inválido',
        'needs_review' => 'Precisa de revisão',
        'created' => 'Criado',
        'skipped' => 'Ignorado',
        'failed' => 'Falhou',
    ],

    'summary' => ':valid válidos, :invalid ignorados',
    'progress' => ':processed de :total processados',
    'process' => 'Criar :count rascunhos',
    'completed' => ':count rascunhos criados e marcados como Content Brief.',
    'view_drafts' => 'Ver rascunhos',
    'new_import' => 'Nova importação',

    'table' => [
        'content' => 'Briefing',
        'status' => 'Status',
        'no_content' => 'Sem conteúdo',
    ],

    'errors' => [
        'upload' => 'Não foi possível enviar este arquivo. Use um CSV com menos de 5 MB.',
        'process' => 'Não foi possível iniciar o processamento. Tente novamente.',
    ],
];
