<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PostImport\Status;
use Database\Factories\PostImportFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PostImport extends Model
{
    /** @use HasFactory<PostImportFactory> */
    use HasFactory, HasUuids;

    protected $fillable = [
        'workspace_id',
        'user_id',
        'original_filename',
        'path',
        'status',
        'total_rows',
        'valid_rows',
        'invalid_rows',
        'created_count',
        'error',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => Status::class,
            'total_rows' => 'integer',
            'valid_rows' => 'integer',
            'invalid_rows' => 'integer',
            'created_count' => 'integer',
        ];
    }

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function rows(): HasMany
    {
        return $this->hasMany(PostImportRow::class)->orderBy('row_number');
    }

    /**
     * @param  Builder<PostImport>  $query
     * @return Builder<PostImport>
     */
    public function scopeOwnedBy(Builder $query, string $workspaceId, string $userId): Builder
    {
        return $query->where('workspace_id', $workspaceId)->where('user_id', $userId);
    }

    public function processedRows(): int
    {
        return $this->rows()
            ->whereIn('status', ['created', 'skipped', 'failed'])
            ->count();
    }
}
