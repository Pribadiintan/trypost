<?php

declare(strict_types=1);

namespace App\Jobs\PostImport;

use App\Actions\Post\CreatePost;
use App\Ai\Agents\PostBriefRefiner;
use App\Enums\Post\CreatedVia;
use App\Enums\PostImport\RowStatus;
use App\Enums\PostImport\Status;
use App\Models\PostImport;
use App\Models\PostImportRow;
use App\Models\User;
use App\Models\Workspace;
use App\Services\Ai\RecordAiUsage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Throwable;

class ProcessPostImport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private const LABEL_NAME = 'Content Brief';

    private const LABEL_COLOR = '#7c3aed';

    public int $timeout = 1800;

    public function __construct(public string $postImportId)
    {
        $this->onQueue('ai');
    }

    public function handle(): void
    {
        $import = PostImport::with('workspace', 'user')->find($this->postImportId);

        if ($import === null || ! in_array($import->status, [Status::PreviewReady, Status::Processing], true)) {
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
        $aiEnabled = Gate::forUser($user)->allows('useAi', $workspace->account);
        $created = $import->created_count;

        $import->rows()
            ->whereIn('status', [RowStatus::Valid->value, RowStatus::NeedsReview->value])
            ->orderBy('row_number')
            ->chunkById(25, function ($rows) use ($workspace, $user, $labelId, $aiEnabled, &$created): void {
                foreach ($rows as $row) {
                    try {
                        $content = $this->buildContent($workspace, $user, $row, $aiEnabled);

                        $post = CreatePost::execute($workspace, $user, [
                            'content' => $content,
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

    private function buildContent(Workspace $workspace, User $user, PostImportRow $row, bool $aiEnabled): string
    {
        $fallback = (string) $row->mapped_content;

        if (! $aiEnabled || $fallback === '') {
            return $fallback;
        }

        $brand = $workspace->resolvedBrand($row->language_code);

        $refined = rescue(function () use ($workspace, $user, $brand, $fallback): ?string {
            $response = (new PostBriefRefiner($workspace, $brand))->prompt($fallback);

            RecordAiUsage::recordText(
                workspace: $workspace,
                promptTokens: $response->usage?->promptTokens ?? 0,
                completionTokens: $response->usage?->completionTokens ?? 0,
                provider: (string) $response->meta?->provider,
                model: (string) $response->meta?->model,
                userId: $user->id,
                metadata: ['agent' => 'post_brief_refiner', 'content_language' => $brand->languageCode],
            );

            return trim((string) $response->text);
        });

        return filled($refined) ? $refined : $fallback;
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
