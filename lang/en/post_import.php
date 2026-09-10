<?php

declare(strict_types=1);

return [
    'title' => 'Import content briefs',
    'description' => 'Upload a CSV of content briefs (up to :max rows). Each row becomes a draft the AI refines to your brand. Generate the final posts later from chat.',
    'upload' => 'Upload CSV',

    'empty' => [
        'title' => 'No import yet',
        'description' => 'Upload a CSV where each row is one content brief.',
    ],

    'status' => [
        'parsing' => 'Parsing',
        'preview_ready' => 'Ready to review',
        'processing' => 'Processing',
        'completed' => 'Completed',
        'failed' => 'Failed',
    ],

    'row_status' => [
        'valid' => 'Valid',
        'invalid' => 'Invalid',
        'needs_review' => 'Needs review',
        'created' => 'Created',
        'skipped' => 'Skipped',
        'failed' => 'Failed',
    ],

    'summary' => ':valid valid, :invalid skipped',
    'progress' => ':processed of :total processed',
    'process' => 'Create :count drafts',
    'completed' => ':count drafts created and labelled Content Brief.',
    'view_drafts' => 'View drafts',
    'new_import' => 'New import',

    'table' => [
        'content' => 'Brief',
        'status' => 'Status',
        'no_content' => 'No content',
    ],

    'errors' => [
        'upload' => 'Could not upload this file. Use a CSV under 5 MB.',
        'process' => 'Could not start processing. Try again.',
    ],
];
