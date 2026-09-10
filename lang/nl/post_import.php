<?php

declare(strict_types=1);

return [
    'title' => 'Contentbriefings importeren',
    'description' => 'Upload een CSV met contentbriefings (max. :max rijen). Elke rij wordt een concept dat de AI afstemt op je merk. Genereer de definitieve posts later vanuit de chat.',
    'upload' => 'CSV uploaden',

    'empty' => [
        'title' => 'Nog geen import',
        'description' => 'Upload een CSV waarin elke rij één contentbriefing is.',
    ],

    'status' => [
        'parsing' => 'Verwerken',
        'preview_ready' => 'Klaar om te bekijken',
        'processing' => 'Bezig',
        'completed' => 'Voltooid',
        'failed' => 'Mislukt',
    ],

    'row_status' => [
        'valid' => 'Geldig',
        'invalid' => 'Ongeldig',
        'needs_review' => 'Controle nodig',
        'created' => 'Aangemaakt',
        'skipped' => 'Overgeslagen',
        'failed' => 'Mislukt',
    ],

    'summary' => ':valid geldig, :invalid overgeslagen',
    'progress' => ':processed van :total verwerkt',
    'process' => ':count concepten aanmaken',
    'completed' => ':count concepten aangemaakt en gelabeld als Content Brief.',
    'view_drafts' => 'Concepten bekijken',
    'new_import' => 'Nieuwe import',

    'table' => [
        'content' => 'Briefing',
        'status' => 'Status',
        'no_content' => 'Geen inhoud',
    ],

    'errors' => [
        'upload' => 'Kon dit bestand niet uploaden. Gebruik een CSV onder 5 MB.',
        'process' => 'Kon verwerking niet starten. Probeer opnieuw.',
    ],
];
