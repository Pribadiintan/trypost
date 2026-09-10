<?php

declare(strict_types=1);

return [
    'title' => 'Importar briefs de contenido',
    'description' => 'Sube un CSV de briefs de contenido (hasta :max filas). Cada fila se convierte en un borrador que la IA adapta a tu marca. Genera las publicaciones finales más tarde desde el chat.',
    'upload' => 'Subir CSV',

    'empty' => [
        'title' => 'Aún no hay importación',
        'description' => 'Sube un CSV donde cada fila sea un brief de contenido.',
    ],

    'status' => [
        'parsing' => 'Analizando',
        'preview_ready' => 'Listo para revisar',
        'processing' => 'Procesando',
        'completed' => 'Completado',
        'failed' => 'Fallido',
    ],

    'row_status' => [
        'valid' => 'Válido',
        'invalid' => 'No válido',
        'needs_review' => 'Necesita revisión',
        'created' => 'Creado',
        'skipped' => 'Omitido',
        'failed' => 'Fallido',
    ],

    'summary' => ':valid válidos, :invalid omitidos',
    'progress' => ':processed de :total procesados',
    'process' => 'Crear :count borradores',
    'completed' => ':count borradores creados y etiquetados como Content Brief.',
    'view_drafts' => 'Ver borradores',
    'new_import' => 'Nueva importación',

    'table' => [
        'content' => 'Brief',
        'status' => 'Estado',
        'no_content' => 'Sin contenido',
    ],

    'errors' => [
        'upload' => 'No se pudo subir este archivo. Usa un CSV de menos de 5 MB.',
        'process' => 'No se pudo iniciar el procesamiento. Inténtalo de nuevo.',
    ],
];
