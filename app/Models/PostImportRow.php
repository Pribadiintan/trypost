<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PostImport\RowStatus;
use Database\Factories\PostImportRowFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PostImportRow extends Model
{
    /** @use HasFactory<PostImportRowFactory> */
    use HasFactory, HasUuids;

    protected $fillable = [
        'post_import_id',
        'row_number',
        'raw',
        'brief',
        'mapped_content',
        'language_code',
        'status',
        'error',
        'post_id',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'raw' => 'array',
            'brief' => 'array',
            'row_number' => 'integer',
            'status' => RowStatus::class,
        ];
    }

    public function import(): BelongsTo
    {
        return $this->belongsTo(PostImport::class, 'post_import_id');
    }

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }
}
