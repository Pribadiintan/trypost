<?php

declare(strict_types=1);

namespace App\Jobs\PostImport;

use App\Enums\PostImport\RowStatus;
use App\Enums\PostImport\Status;
use App\Models\PostImport;
use App\Services\PostImport\PostImportParser;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Throwable;

class ParsePostImport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public string $postImportId)
    {
        $this->onQueue('ai');
    }

    public function handle(PostImportParser $parser): void
    {
        $import = PostImport::find($this->postImportId);

        if ($import === null || $import->status !== Status::Parsing) {
            return;
        }

        try {
            $absolutePath = Storage::path($import->path);
            $result = $parser->parse($absolutePath);

            $valid = 0;
            $invalid = 0;

            foreach ($result['rows'] as $row) {
                $import->rows()->create([
                    'row_number' => $row['row_number'],
                    'raw' => $row['raw'],
                    'brief' => $row['brief'],
                    'mapped_content' => $row['mapped_content'],
                    'language_code' => $row['language_code'],
                    'status' => $row['status'],
                    'error' => $row['error'],
                ]);

                if ($row['status'] === RowStatus::Invalid) {
                    $invalid++;
                } else {
                    $valid++;
                }
            }

            $import->update([
                'total_rows' => count($result['rows']),
                'valid_rows' => $valid,
                'invalid_rows' => $invalid,
                'status' => Status::PreviewReady,
            ]);
        } catch (Throwable $e) {
            $import->update([
                'status' => Status::Failed,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
