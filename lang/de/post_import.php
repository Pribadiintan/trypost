<?php

declare(strict_types=1);

return [
    'title' => 'Content-Briefings importieren',
    'description' => 'Lade eine CSV mit Content-Briefings hoch (bis zu :max Zeilen). Jede Zeile wird zu einem Entwurf, den die KI an deine Marke anpasst. Erzeuge die finalen Beiträge später im Chat.',
    'upload' => 'CSV hochladen',

    'empty' => [
        'title' => 'Noch kein Import',
        'description' => 'Lade eine CSV hoch, bei der jede Zeile ein Content-Briefing ist.',
    ],

    'status' => [
        'parsing' => 'Wird analysiert',
        'preview_ready' => 'Bereit zur Prüfung',
        'processing' => 'Wird verarbeitet',
        'completed' => 'Abgeschlossen',
        'failed' => 'Fehlgeschlagen',
    ],

    'row_status' => [
        'valid' => 'Gültig',
        'invalid' => 'Ungültig',
        'needs_review' => 'Prüfung nötig',
        'created' => 'Erstellt',
        'skipped' => 'Übersprungen',
        'failed' => 'Fehlgeschlagen',
    ],

    'summary' => ':valid gültig, :invalid übersprungen',
    'progress' => ':processed von :total verarbeitet',
    'process' => ':count Entwürfe erstellen',
    'completed' => ':count Entwürfe erstellt und mit Content Brief gekennzeichnet.',
    'view_drafts' => 'Entwürfe ansehen',
    'new_import' => 'Neuer Import',

    'table' => [
        'content' => 'Briefing',
        'status' => 'Status',
        'no_content' => 'Kein Inhalt',
    ],

    'errors' => [
        'upload' => 'Datei konnte nicht hochgeladen werden. Verwende eine CSV unter 5 MB.',
        'process' => 'Verarbeitung konnte nicht gestartet werden. Versuche es erneut.',
    ],
];
