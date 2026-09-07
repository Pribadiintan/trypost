<?php

declare(strict_types=1);

use App\Enums\SocialAccount\Platform;
use App\Enums\UserWorkspace\Role;
use App\Jobs\Ai\StreamPostCreation;
use App\Models\AiGeneration;
use App\Models\SocialAccount;
use App\Models\Workspace;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia;

beforeEach(function (): void {
    Bus::fake();

    [$this->user, $this->workspace] = actingAsWorkspaceUserWithRole(Role::Member);

    $this->account = SocialAccount::factory()->for($this->workspace)->create([
        'platform' => Platform::Threads,
    ]);
});

it('renders the create page with the workspace generation catalog', function (): void {
    $this->get(route('app.posts.create'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('posts/Create')
            ->has('catalog.formats')
            ->has('catalog.styles')
            ->has('catalog.languages'));
});

it('starts a generation and returns a creation id and channel', function (): void {
    $response = $this->postJson(route('app.posts.ai.start'), [
        'prompt' => 'Write a post about our new pricing page',
        'format' => 'threads_post',
        'style' => 'image_card',
        'image_count' => 1,
        'social_account_id' => $this->account->id,
    ]);

    $response->assertStatus(202);

    $creationId = $response->json('creation_id');

    expect($creationId)->not->toBeEmpty()
        ->and($response->json('channel'))->toBe("user.{$this->user->id}.ai-creation.{$creationId}");

    Bus::assertDispatched(StreamPostCreation::class);
});

it('rejects a format the workspace catalog does not offer', function (): void {
    $this->postJson(route('app.posts.ai.start'), [
        'prompt' => 'Write a post about our new pricing page',
        'format' => 'linkedin_post',
        'style' => 'image_card',
    ])->assertStatus(422)->assertJsonValidationErrors('format');
});

it('rejects a prompt shorter than the minimum', function (): void {
    $this->postJson(route('app.posts.ai.start'), [
        'prompt' => 'ab',
        'format' => 'threads_post',
        'style' => 'image_card',
    ])->assertStatus(422)->assertJsonValidationErrors('prompt');
});

it('resolves a generation by its creation id', function (): void {
    $creationId = (string) Str::uuid();

    AiGeneration::query()->create([
        'workspace_id' => $this->workspace->id,
        'user_id' => $this->user->id,
        'creation_id' => $creationId,
        'status' => 'ready',
        'format' => 'threads_post',
        'template' => 'image_card',
        'image_expected' => 0,
        'image_done' => 0,
    ]);

    $this->get(route('app.posts.ai.status', $creationId))
        ->assertOk()
        ->assertJsonPath('status', 'ready');
});

it('does not resolve a generation from another workspace', function (): void {
    $creationId = (string) Str::uuid();

    AiGeneration::query()->create([
        'workspace_id' => Workspace::factory()->create()->id,
        'user_id' => $this->user->id,
        'creation_id' => $creationId,
        'status' => 'ready',
        'format' => 'threads_post',
        'template' => 'image_card',
        'image_expected' => 0,
        'image_done' => 0,
    ]);

    $this->get(route('app.posts.ai.status', $creationId))->assertNotFound();
});
