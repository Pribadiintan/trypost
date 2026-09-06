<?php

declare(strict_types=1);

use App\Ai\Agents\PostContentGenerator;
use App\Ai\Agents\PostContentHumanizer;
use App\Ai\Tools\ToolReplayer;
use App\Enums\Ai\GenerationStatus;
use App\Enums\UserWorkspace\Role;
use App\Enums\WorkspaceConversation\Message\Role as MessageRole;
use App\Jobs\Ai\RenderPostImages;
use App\Jobs\Ai\StreamPostCreation;
use App\Models\AiGeneration;
use App\Models\SocialAccount;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceConversation;
use App\Models\WorkspaceConversationMessage;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Laravel\Ai\Image;

beforeEach(function () {
    Bus::fake();
    Storage::fake();
    Image::fake();

    $this->user = User::factory()->create();
    $this->workspace = Workspace::factory()->create(['user_id' => $this->user->id]);
    $this->workspace->members()->attach($this->user->id, ['role' => Role::Member->value]);
    $this->user->update(['current_workspace_id' => $this->workspace->id]);

    $this->account = SocialAccount::factory()->instagram()->create([
        'workspace_id' => $this->workspace->id,
    ]);
});

test('text phase creates a draft post linked by creation_id and marks text_ready', function () {
    PostContentGenerator::fake([[
        'content' => 'A single productivity tip',
        'image_title' => 'Tip',
        'image_body' => 'Do less',
        'image_keywords' => [],
    ]]);
    PostContentHumanizer::fake([[
        'content' => 'A single productivity tip',
        'image_title' => 'Tip',
        'image_body' => 'Do less',
    ]]);

    $creationId = (string) Str::uuid();

    (new StreamPostCreation(
        userId: $this->user->id,
        creationId: $creationId,
        workspaceId: $this->workspace->id,
        format: 'instagram_feed',
        socialAccountId: $this->account->id,
        imageCount: 1,
        prompt: 'Five tips about productivity',
        template: 'image_card',
    ))->handle();

    $generation = AiGeneration::query()->where('creation_id', $creationId)->firstOrFail();
    $post = $this->workspace->posts()->where('creation_id', $creationId)->firstOrFail();

    expect($generation->status)->toBe(GenerationStatus::TextReady)
        ->and($generation->post_id)->toBe($post->id)
        ->and($generation->image_expected)->toBe(1)
        ->and($generation->image_done)->toBe(0)
        ->and($post->media)->toHaveCount(0);
});

test('image phase producing zero images marks failed_image instead of ready', function () {
    PostContentGenerator::fake([[
        'caption' => 'Swipe to see the tips',
        'slides' => [
            ['title' => 'Tip 1', 'body' => 'First tip', 'image_keywords' => []],
            ['title' => 'Tip 2', 'body' => 'Second tip', 'image_keywords' => []],
        ],
    ]]);
    PostContentHumanizer::fake([[
        'caption' => 'Swipe to see the tips',
        'slides' => [
            ['title' => 'Tip 1', 'body' => 'First tip'],
            ['title' => 'Tip 2', 'body' => 'Second tip'],
        ],
    ]]);

    // Empty keywords make the image client return null without calling a provider.
    $creationId = (string) Str::uuid();

    (new StreamPostCreation(
        userId: $this->user->id,
        creationId: $creationId,
        workspaceId: $this->workspace->id,
        format: 'instagram_carousel',
        socialAccountId: $this->account->id,
        imageCount: 2,
        prompt: 'Five tips about productivity',
        template: 'image_card',
    ))->handle();

    (new RenderPostImages(
        userId: $this->user->id,
        creationId: $creationId,
        workspaceId: $this->workspace->id,
    ))->handle();

    $generation = AiGeneration::query()->where('creation_id', $creationId)->firstOrFail();
    $post = $this->workspace->posts()->where('creation_id', $creationId)->firstOrFail();

    expect($generation->status)->toBe(GenerationStatus::FailedImage)
        ->and($generation->error_phase)->toBe('image')
        ->and($post->content)->toBe('Swipe to see the tips')
        ->and($post->media)->toHaveCount(0);
});

test('replay merges phased generation status so refresh never restarts the loader', function () {
    $generation = AiGeneration::factory()->for($this->workspace)->for($this->user)->create([
        'creation_id' => 'call_phased',
        'status' => GenerationStatus::FailedImage,
        'format' => 'instagram_carousel',
        'template' => 'image_card',
        'image_expected' => 2,
        'image_done' => 0,
        'error_phase' => 'image',
        'error' => 'Only 0 of 2 images could be rendered.',
    ]);

    $conversation = WorkspaceConversation::factory()->for($this->workspace)->for($this->user)->create();

    $stored = json_encode(['data' => ['creation_id' => 'call_phased', 'channel' => "user.{$this->user->id}.ai-creation.call_phased"]]);

    WorkspaceConversationMessage::factory()->for($conversation, 'conversation')->create([
        'role' => MessageRole::Assistant,
        'content' => 'Generating it now.',
        'tool_calls' => [['id' => 'call_phased', 'name' => 'generate_post', 'arguments' => ['prompt' => 'hello']]],
        'tool_results' => [['id' => 'call_phased', 'result' => $stored]],
    ]);

    $payloads = app(ToolReplayer::class)->replay($conversation);

    $data = data_get(json_decode($payloads['call_phased'], true), 'data');

    expect(data_get($data, 'generation.status'))->toBe(GenerationStatus::FailedImage->value)
        ->and(data_get($data, 'generation.image_expected'))->toBe(2)
        ->and(data_get($data, 'generation.error_phase'))->toBe('image');
});

test('a retry adopts the orphaned draft instead of inserting a second post', function () {
    PostContentGenerator::fake([[
        'content' => 'Retried caption',
        'image_title' => 'Tip',
        'image_body' => 'Do less',
        'image_keywords' => [],
    ]]);
    PostContentHumanizer::fake([[
        'content' => 'Retried caption',
        'image_title' => 'Tip',
        'image_body' => 'Do less',
    ]]);

    $creationId = (string) Str::uuid();

    $orphan = $this->workspace->posts()->create([
        'user_id' => $this->user->id,
        'content' => 'Stale caption from the crashed attempt',
        'media' => [],
        'status' => 'draft',
        'creation_id' => $creationId,
    ]);

    (new StreamPostCreation(
        userId: $this->user->id,
        creationId: $creationId,
        workspaceId: $this->workspace->id,
        format: 'instagram_feed',
        socialAccountId: $this->account->id,
        imageCount: 0,
        prompt: 'Five tips about productivity',
        template: 'image_card',
    ))->handle();

    expect($this->workspace->posts()->where('creation_id', $creationId)->count())->toBe(1)
        ->and($orphan->fresh()->content)->toBe('Retried caption');
});

test('replay augments a retry_post_images payload from the same generation', function () {
    $post = $this->workspace->posts()->create([
        'user_id' => $this->user->id,
        'content' => 'Saved draft text',
        'media' => [],
        'status' => 'draft',
        'creation_id' => 'call_retry_card',
    ]);

    AiGeneration::factory()->for($this->workspace)->for($this->user)->create([
        'creation_id' => 'call_retry_card',
        'status' => GenerationStatus::FailedImage,
        'format' => 'telegram_post',
        'template' => 'image_card',
        'image_expected' => 1,
        'image_done' => 0,
        'post_id' => $post->id,
        'error_phase' => 'image',
        'error' => 'Only 0 of 1 images could be rendered.',
    ]);

    $conversation = WorkspaceConversation::factory()->for($this->workspace)->for($this->user)->create();

    $stored = json_encode(['data' => ['creation_id' => 'call_retry_card', 'channel' => "user.{$this->user->id}.ai-creation.call_retry_card"]]);

    WorkspaceConversationMessage::factory()->for($conversation, 'conversation')->create([
        'role' => MessageRole::Assistant,
        'content' => 'Retrying the images.',
        'tool_calls' => [['id' => 'call_retry_card', 'name' => 'retry_post_images', 'arguments' => ['creation_id' => 'call_retry_card']]],
        'tool_results' => [['id' => 'call_retry_card', 'result' => $stored]],
    ]);

    $payloads = app(ToolReplayer::class)->replay($conversation);

    $data = data_get(json_decode($payloads['call_retry_card'], true), 'data');

    expect(data_get($data, 'generation.status'))->toBe(GenerationStatus::FailedImage->value)
        ->and(data_get($data, 'post.id'))->toBe($post->id);
});

test('replay settles a ready generation whose post was deleted', function () {
    AiGeneration::factory()->for($this->workspace)->for($this->user)->create([
        'creation_id' => 'call_deleted',
        'status' => GenerationStatus::Ready,
        'format' => 'instagram_feed',
        'template' => 'image_card',
        'image_expected' => 0,
        'image_done' => 0,
        'post_id' => null,
    ]);

    $conversation = WorkspaceConversation::factory()->for($this->workspace)->for($this->user)->create();

    $stored = json_encode(['data' => ['creation_id' => 'call_deleted', 'channel' => "user.{$this->user->id}.ai-creation.call_deleted"]]);

    WorkspaceConversationMessage::factory()->for($conversation, 'conversation')->create([
        'role' => MessageRole::Assistant,
        'content' => 'Generating it now.',
        'tool_calls' => [['id' => 'call_deleted', 'name' => 'generate_post', 'arguments' => ['prompt' => 'hello']]],
        'tool_results' => [['id' => 'call_deleted', 'result' => $stored]],
    ]);

    $payloads = app(ToolReplayer::class)->replay($conversation);

    $data = data_get(json_decode($payloads['call_deleted'], true), 'data');

    expect(data_get($data, 'generation.status'))->toBe(GenerationStatus::Ready->value)
        ->and(data_get($data, 'settled'))->toBeTrue()
        ->and(data_get($data, 'post'))->toBeNull();
});
