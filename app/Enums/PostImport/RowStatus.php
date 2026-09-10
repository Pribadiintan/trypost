<?php

declare(strict_types=1);

namespace App\Enums\PostImport;

enum RowStatus: string
{
    case Valid = 'valid';
    case Invalid = 'invalid';
    case NeedsReview = 'needs_review';
    case Created = 'created';
    case Skipped = 'skipped';
    case Failed = 'failed';
}
