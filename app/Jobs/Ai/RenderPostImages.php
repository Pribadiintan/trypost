<?php

declare(strict_types=1);

namespace App\Jobs\Ai;

use App\Ai\Templates\AiTemplateRegistry;
use App\Ai\Templates\TemplateContext;
use App\Enums\Ai\GenerationStatus;
use App\Enums\Notification\Channel as NotificationChannel;
use App\Enums\Notification\Type as NotificationType;
use App\Enums\PostPlatform\ContentType;
use App\Events\Ai\PostCreationProgress;
use App\Events\Ai\PostCreationReady;
use App\Jobs\SendNotification;
use App\Models\AiGeneration;
use App\Models\SocialAccount;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Image phase of AI post generation (image model only).
 *
 * Dispatched by {@see StreamPostCreation} after the text phase created the
 * draft post and persisted the structured slides on the generation. Renders
 * every image via the template, replaces the post media idempotently so a
 * retry never duplicates, and marks the generation ready only when the
 * expected image count was actually produced.
 */
class RenderPostImages implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $uniqueFor = 990;

    public function __construct(
        public string $userId,
        public string $creationId,
        public string $workspaceId,
        public bool $applyBrandVisuals = true,
        public array $referenceMediaIds = [],
        public bool $useBrandReferences = true,
    ) {
        $this->onQueue('ai');
    }

    public function uniqueId(): string
    {
        return "{$this->userId}:{$this->creationId}:images";
    }

    public function failed(?Throwable $exception): void
    {
        $generation = AiGeneration::query()
            ->where('workspace_id', $this->workspaceId)
            ->where('creation_id', $this->creationId)
            ->first();

        if ($generation === null || $generation->status->isTerminal()) {
            return;
        }

        $generation->update([
            'status' => GenerationStatus::FailedImage,
            'error_phase' => 'image',
            'error' => $exception?->getMessage() ?? 'Image rendering failed.',
        ]);

        Log::error('RenderPostImages failed', [
            'creation_id' => $this->creationId,
            'error' => $exception?->getMessage(),
        ]);

        PostCreationReady::dispatch($this->userId, $this->creationId, $generation->post_id, $generation->error);
    }

    public function handle(): void
    {
        $generation = AiGeneration::query()
            ->where('workspace_id', $this->workspaceId)
            ->where('creation_id', $this->creationId)
            ->firstOrFail();

        if ($generation->status->isTerminal()) {
            return;
        }

        $workspace = Workspace::findOrFail($this->workspaceId);
        $post = $workspace->posts()->whereKey($generation->post_id)->firstOrFail();
        $socialAccount = $generation->social_account_id !== null
            ? SocialAccount::find($generation->social_account_id)
            : null;

        $structured = $generation->structured ?? [];
        $style = app(AiTemplateRegistry::class)->find($generation->template);
        $brand = $workspace->resolvedBrand();

        $referenceImages = [];
        if ($this->referenceMediaIds !== []) {
            $referenceImages = $workspace->media()
                ->whereIn('id', $this->referenceMediaIds)
                ->pluck('path')
                ->all();
        } elseif ($this->useBrandReferences) {
            $referenceImages = $workspace->getMedia('brand_references')
                ->pluck('path')
                ->all();
        }

        $isCarousel = $generation->format === ContentType::CAROUSEL_FORMAT;

        $context = new TemplateContext(
            workspace: $workspace,
            socialAccount: $socialAccount,
            format: $generation->format,
            imageCount: $generation->image_expected,
            isCarousel: $isCarousel,
            applyBrandVisuals: $this->applyBrandVisuals,
            languageCode: $brand->languageCode,
            brand: $brand,
            referenceImages: $referenceImages,
        );

        $generation->update(['status' => GenerationStatus::ImageRunning]);

        PostCreationProgress::dispatch(
            userId: $this->userId,
            creationId: $this->creationId,
            phase: GenerationStatus::ImageRunning,
            postId: $post->id,
            imageDone: 0,
            imageExpected: $generation->image_expected,
        );

        try {
            $generated = $style->assemble($structured, $context);
            $media = $generated->media;
            $done = count($media);

            $post->update(['media' => $media]);

            $generation->update(['image_done' => $done]);

            if ($generation->image_expected > 0 && $done < $generation->image_expected) {
                $generation->update([
                    'status' => GenerationStatus::FailedImage,
                    'error_phase' => 'image',
                    'error' => "Only {$done} of {$generation->image_expected} images could be rendered. The draft text is saved — ask again to retry the images.",
                ]);

                PostCreationReady::dispatch($this->userId, $this->creationId, $post->id, (string) $generation->error);

                return;
            }

            $generation->update(['status' => GenerationStatus::Ready]);

            PostCreationReady::dispatch(
                userId: $this->userId,
                creationId: $this->creationId,
                postId: $post->id,
            );

            $user = User::findOrFail($this->userId);

            SendNotification::dispatch(
                user: $user,
                workspaceId: $workspace->id,
                type: NotificationType::PostReady,
                channel: NotificationChannel::InApp,
                title: trans('notifications.post_ready.title', [], $workspace->content_language),
                body: trans('notifications.post_ready.body', [], $workspace->content_language),
                data: ['post_id' => $post->id],
            );
        } catch (Throwable $e) {
            $generation->update([
                'status' => GenerationStatus::FailedImage,
                'error_phase' => 'image',
                'error' => $e->getMessage(),
            ]);

            Log::error('RenderPostImages failed', [
                'creation_id' => $this->creationId,
                'error' => $e->getMessage(),
            ]);

            PostCreationReady::dispatch($this->userId, $this->creationId, $post->id, $e->getMessage());

            throw $e;
        }
    }
}
