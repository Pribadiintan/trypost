<?php

declare(strict_types=1);

namespace App\Jobs\PostImport;

use App\Actions\Post\CreatePost;
use App\Enums\Post\CreatedVia;
use App\Enums\PostImport\RowStatus;
use App\Enums\PostImport\Status;
use App\Models\PostImport;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Throwable;

class ProcessPostImport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private const LABEL_NAME = 'Content Brief';

    private const LABEL_COLOR = '#7c3aed';

    public function __construct(public string $postImportId)
    {
        $this->onQueue('ai');
    }

    public function handle(): void
    {
        $import = PostImport::with('workspace', 'user')->find($this->postImportId);

        if ($import === null || $import->status !== Status::PreviewReady) {
            return;
        }

        $workspace = $import->workspace;
        $user = $import->user;

        if (! $workspace instanceof Workspace || ! $user instanceof User) {
            $import->update(['status' => Status::Failed, 'error' => 'missing_owner']);

            return;
        }

        $import->update(['status' => Status::Processing]);

        $labelId = $this->resolveLabelId($workspace);
        $created = $import->created_count;

        $import->rows()
            ->whereIn('status', [RowStatus::Valid->value, RowStatus::NeedsReview->value])
            ->orderBy('row_number')
            ->chunkById(25, function ($rows) use ($workspace, $user, $labelId, &$created): void {
                foreach ($rows as $row) {
                    try {
                        $post = CreatePost::execute($workspace, $user, [
                            'content' => (string) $row->mapped_content,
                            'created_via' => CreatedVia::Import,
                            'label_ids' => [$labelId],
                        ]);

                        $row->update(['status' => RowStatus::Created, 'post_id' => $post->id]);
                        $created++;
                    } catch (Throwable $e) {
                        $row->update(['status' => RowStatus::Failed, 'error' => mb_substr($e->getMessage(), 0, 500)]);
                    }
                }
            });

        $import->update([
            'created_count' => $created,
            'status' => Status::Completed,
        ]);

        Storage::delete($import->path);
    }

    private function resolveLabelId(Workspace $workspace): string
    {
        $label = $workspace->labels()->firstOrCreate(
            ['name' => self::LABEL_NAME],
            ['color' => self::LABEL_COLOR],
        );

        return $label->id;
    }
}
