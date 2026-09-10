<?php

declare(strict_types=1);

use App\Ai\Agents\PostBriefRefiner;
use App\Enums\Post\CreatedVia;
use App\Enums\Post\Status as PostStatus;
use App\Enums\PostImport\RowStatus;
use App\Enums\PostImport\Status;
use App\Enums\UserWorkspace\Role;
use App\Jobs\PostImport\ParsePostImport;
use App\Jobs\PostImport\ProcessPostImport;
use App\Models\Post;
use App\Models\PostImport;
use App\Models\User;
use App\Models\Workspace;
use App\Services\PostImport\PostImportParser;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->workspace = Workspace::factory()->create(['user_id' => $this->user->id]);
    $this->workspace->members()->attach($this->user->id, ['role' => Role::Member->value]);
    $this->user->update(['current_workspace_id' => $this->workspace->id]);
});

function writeCsv(string $contents): string
{
    $path = 'post-imports/'.uniqid().'.csv';
    Storage::put($path, $contents);

    return $path;
}

test('parser maps a rich brief row into structured content and marks it valid', function () {
    $csv = "topic,key_insight,target_audience,tone,content_goal\n"
        ."Why AI fails,Start with the problem,Founders,Strategic,Educate\n";

    $path = writeCsv($csv);
    $result = app(PostImportParser::class)->parse(Storage::path($path));

    expect($result['rows'])->toHaveCount(1);
    expect($result['rows'][0]['status'])->toBe(RowStatus::Valid);
    expect($result['rows'][0]['mapped_content'])->toContain('Topic: Why AI fails');
    expect($result['rows'][0]['mapped_content'])->toContain('Key insight: Start with the problem');
});

test('parser flags a sparse row as needs_review', function () {
    $csv = "topic,tone\n"
        ."Just a topic,Casual\n";

    $path = writeCsv($csv);
    $result = app(PostImportParser::class)->parse(Storage::path($path));

    expect($result['rows'][0]['status'])->toBe(RowStatus::NeedsReview);
});

test('parser marks a row with no topic content or description as invalid', function () {
    $csv = "tone,content_goal\n"
        ."Casual,Educate\n";

    $path = writeCsv($csv);
    $result = app(PostImportParser::class)->parse(Storage::path($path));

    expect($result['rows'][0]['status'])->toBe(RowStatus::Invalid);
    expect($result['rows'][0]['error'])->toBe('missing_idea');
});

test('parser resolves the language column to a supported code', function () {
    $csv = "topic,language\n"
        ."Hello world,English\n";

    $path = writeCsv($csv);
    $result = app(PostImportParser::class)->parse(Storage::path($path));

    expect($result['rows'][0]['language_code'])->toBe('en');
});

test('parse job persists rows and moves the import to preview_ready', function () {
    $csv = "topic,key_insight,target_audience,tone,content_goal\n"
        ."A,B,C,D,E\n"
        ."only topic,,,,\n"
        .",,,,\n";

    $import = PostImport::factory()->create([
        'workspace_id' => $this->workspace->id,
        'user_id' => $this->user->id,
        'path' => writeCsv($csv),
    ]);

    ParsePostImport::dispatchSync($import->id);

    $import->refresh();

    expect($import->status)->toBe(Status::PreviewReady);
    expect($import->total_rows)->toBe(2);
    expect($import->valid_rows)->toBe(2);
    expect($import->invalid_rows)->toBe(0);
    expect($import->rows()->count())->toBe(2);
});

test('process job refines each valid row with AI into an Import draft labelled Content Brief', function () {
    config()->set('trypost.self_hosted', true);
    PostBriefRefiner::fake(['Refined caption one.', 'Refined caption two.']);

    $csv = "topic,key_insight,target_audience,tone,content_goal\n"
        ."A,B,C,D,E\n"
        ."F,G,H,I,J\n";

    $import = PostImport::factory()->create([
        'workspace_id' => $this->workspace->id,
        'user_id' => $this->user->id,
        'path' => writeCsv($csv),
    ]);

    ParsePostImport::dispatchSync($import->id);
    ProcessPostImport::dispatchSync($import->id);

    $import->refresh();

    expect($import->status)->toBe(Status::Completed);
    expect($import->created_count)->toBe(2);

    $posts = Post::where('workspace_id', $this->workspace->id)->get();
    expect($posts)->toHaveCount(2);
    expect($posts->pluck('content')->all())->toEqualCanonicalizing([
        'Refined caption one.',
        'Refined caption two.',
    ]);
    expect($posts->every(fn (Post $post): bool => $post->created_via === CreatedVia::Import))->toBeTrue();
    expect($posts->every(fn (Post $post): bool => $post->status === PostStatus::Draft))->toBeTrue();

    $label = $this->workspace->labels()->where('name', 'Content Brief')->first();
    expect($label)->not->toBeNull();
    expect($posts->first()->labels()->where('workspace_labels.id', $label->id)->exists())->toBeTrue();
});

test('process job falls back to the mapped brief when AI is unavailable', function () {
    config()->set('trypost.self_hosted', false);

    $csv = "topic,key_insight,target_audience,tone,content_goal\nA,B,C,D,E\n";

    $import = PostImport::factory()->create([
        'workspace_id' => $this->workspace->id,
        'user_id' => $this->user->id,
        'path' => writeCsv($csv),
    ]);

    ParsePostImport::dispatchSync($import->id);
    ProcessPostImport::dispatchSync($import->id);

    $post = Post::where('workspace_id', $this->workspace->id)->first();
    expect($post)->not->toBeNull();
    expect($post->content)->toContain('Topic: A');
});

test('process job is idempotent and does not duplicate drafts on a second run', function () {
    config()->set('trypost.self_hosted', true);
    PostBriefRefiner::fake(['Refined caption.', 'Refined caption again.']);

    $csv = "topic,key_insight,target_audience,tone,content_goal\nA,B,C,D,E\n";

    $import = PostImport::factory()->create([
        'workspace_id' => $this->workspace->id,
        'user_id' => $this->user->id,
        'path' => writeCsv($csv),
    ]);

    ParsePostImport::dispatchSync($import->id);
    ProcessPostImport::dispatchSync($import->id);
    ProcessPostImport::dispatchSync($import->id);

    expect(Post::where('workspace_id', $this->workspace->id)->count())->toBe(1);
});

test('store endpoint accepts a csv and dispatches parsing', function () {
    Storage::fake();

    $file = UploadedFile::fake()->createWithContent(
        'briefs.csv',
        "topic,tone\nHello,Casual\n",
    );

    $response = $this->actingAs($this->user)
        ->postJson(route('app.post-imports.store'), ['file' => $file]);

    $response->assertCreated();
    $response->assertJsonPath('status', Status::Parsing->value);

    expect(PostImport::where('workspace_id', $this->workspace->id)->count())->toBe(1);
});

test('show endpoint returns progress and 404s another users import', function () {
    $import = PostImport::factory()->previewReady()->create([
        'workspace_id' => $this->workspace->id,
        'user_id' => $this->user->id,
        'total_rows' => 3,
        'valid_rows' => 2,
    ]);

    $this->actingAs($this->user)
        ->getJson(route('app.post-imports.show', $import->id))
        ->assertOk()
        ->assertJsonPath('status', Status::PreviewReady->value)
        ->assertJsonPath('total_rows', 3);

    $other = User::factory()->create();
    $otherWorkspace = Workspace::factory()->create(['user_id' => $other->id]);
    $otherWorkspace->members()->attach($other->id, ['role' => Role::Member->value]);
    $other->update(['current_workspace_id' => $otherWorkspace->id]);

    $this->actingAs($other)
        ->getJson(route('app.post-imports.show', $import->id))
        ->assertNotFound();
});

test('process endpoint refuses an import that is not preview_ready', function () {
    $import = PostImport::factory()->create([
        'workspace_id' => $this->workspace->id,
        'user_id' => $this->user->id,
        'status' => Status::Parsing,
    ]);

    $this->actingAs($this->user)
        ->postJson(route('app.post-imports.process', $import->id))
        ->assertConflict();
});

test('CreatedVia has an Import case', function () {
    expect(CreatedVia::Import->value)->toBe('import');
});

test('import index page renders with the row cap', function () {
    $this->actingAs($this->user)
        ->get(route('app.post-imports.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('posts/Import', false)
            ->where('maxRows', 50)
        );
});
