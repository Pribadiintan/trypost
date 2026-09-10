<?php

declare(strict_types=1);

namespace App\Enums\PostImport;

enum Status: string
{
    case Parsing = 'parsing';
    case PreviewReady = 'preview_ready';
    case Processing = 'processing';
    case Completed = 'completed';
    case Failed = 'failed';
}
