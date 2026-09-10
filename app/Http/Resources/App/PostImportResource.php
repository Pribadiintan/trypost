<?php

declare(strict_types=1);

namespace App\Http\Resources\App;

use App\Models\PostImport;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin PostImport
 */
class PostImportResource extends JsonResource
{
    private bool $includeRows = false;

    public function withRows(): self
    {
        $this->includeRows = true;

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $data = [
            'id' => $this->id,
            'original_filename' => $this->original_filename,
            'status' => $this->status->value,
            'total_rows' => $this->total_rows,
            'valid_rows' => $this->valid_rows,
            'invalid_rows' => $this->invalid_rows,
            'processed_rows' => $this->processedRows(),
            'created_count' => $this->created_count,
            'error' => $this->error,
        ];

        if ($this->includeRows) {
            $data['rows'] = $this->rows->map(fn ($row): array => [
                'id' => $row->id,
                'row_number' => $row->row_number,
                'status' => $row->status->value,
                'error' => $row->error,
                'language_code' => $row->language_code,
                'mapped_content' => $row->mapped_content,
                'post_id' => $row->post_id,
            ])->all();
        }

        return $data;
    }
}
