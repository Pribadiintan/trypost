<?php

declare(strict_types=1);

return [
    'title' => 'Importer des briefs de contenu',
    'description' => 'Importez un CSV de briefs de contenu (jusqu\'à :max lignes). Chaque ligne devient un brouillon que l\'IA adapte à votre marque. Générez les publications finales plus tard depuis le chat.',
    'upload' => 'Importer un CSV',

    'empty' => [
        'title' => 'Aucune importation',
        'description' => 'Importez un CSV où chaque ligne est un brief de contenu.',
    ],

    'status' => [
        'parsing' => 'Analyse',
        'preview_ready' => 'Prêt à vérifier',
        'processing' => 'Traitement',
        'completed' => 'Terminé',
        'failed' => 'Échec',
    ],

    'row_status' => [
        'valid' => 'Valide',
        'invalid' => 'Non valide',
        'needs_review' => 'À vérifier',
        'created' => 'Créé',
        'skipped' => 'Ignoré',
        'failed' => 'Échec',
    ],

    'summary' => ':valid valides, :invalid ignorés',
    'progress' => ':processed sur :total traités',
    'process' => 'Créer :count brouillons',
    'completed' => ':count brouillons créés et étiquetés Content Brief.',
    'view_drafts' => 'Voir les brouillons',
    'new_import' => 'Nouvelle importation',

    'table' => [
        'content' => 'Brief',
        'status' => 'Statut',
        'no_content' => 'Aucun contenu',
    ],

    'errors' => [
        'upload' => 'Impossible d\'importer ce fichier. Utilisez un CSV de moins de 5 Mo.',
        'process' => 'Impossible de démarrer le traitement. Réessayez.',
    ],
];
