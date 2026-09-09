<?php

declare(strict_types=1);

use App\Enums\Workspace\ImageStyle;
use App\Services\Ai\AiImageClient;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Laravel\Ai\Image;
use Laravel\Ai\Prompts\ImagePrompt;
use Laravel\Ai\Responses\Data\Meta;
use Laravel\Ai\Responses\Data\Usage;
use Laravel\Ai\Responses\ImageResponse;

test('generate returns null when keywords are empty', function () {
    Image::fake();

    $client = new AiImageClient;

    expect($client->generate([], ImageStyle::Cinematic))->toBeNull();
    Image::assertNothingGenerated();
});

test('generate returns bytes plus the resolved provider and model when AI succeeds', function () {
    $bytes = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==');
    Image::fake([base64_encode($bytes)]);

    $client = new AiImageClient;

    $result = $client->generate(['kitchen', 'morning'], ImageStyle::Illustration);

    expect($result)
        ->not->toBeNull()
        ->and($result['bytes'])->toBe($bytes)
        ->and($result['provider'])->toBe('openai')
        ->and($result['model'])->toBe('gpt-image-2');
});

test('generate honours AI_IMAGE_PROVIDER instead of always using OpenAI', function () {
    config()->set('ai.default_for_images', 'gemini');
    Image::fake();

    $client = new AiImageClient;

    $result = $client->generate(['kitchen'], ImageStyle::Illustration);

    expect($result)
        ->not->toBeNull()
        ->and($result['provider'])->toBe('gemini');
});

test('generate uses style-specific prompt prefix', function () {
    Image::fake();

    $client = new AiImageClient;
    $client->generate(['mountain hiker'], ImageStyle::Cinematic);

    Image::assertGenerated(fn (ImagePrompt $prompt) => $prompt->contains('Cinematic photograph')
        && $prompt->contains('mountain hiker'));
});

test('generate maps orientation to portrait', function () {
    Image::fake();

    $client = new AiImageClient;
    $client->generate(['x'], ImageStyle::Cinematic, orientation: 'portrait');

    Image::assertGenerated(fn (ImagePrompt $prompt) => $prompt->isPortrait());
});

test('generate maps orientation to landscape', function () {
    Image::fake();

    $client = new AiImageClient;
    $client->generate(['x'], ImageStyle::Cinematic, orientation: 'landscape');

    Image::assertGenerated(fn (ImagePrompt $prompt) => $prompt->isLandscape());
});

test('generate falls back to square for unknown orientation', function () {
    Image::fake();

    $client = new AiImageClient;
    $client->generate(['x'], ImageStyle::Cinematic, orientation: 'whatever');

    Image::assertGenerated(fn (ImagePrompt $prompt) => $prompt->isSquare());
});

test('generate appends Brazilian Portuguese instruction when language is pt-BR', function () {
    Image::fake();

    $client = new AiImageClient;
    $client->generate(['x'], ImageStyle::Cinematic, language: 'pt-BR');

    Image::assertGenerated(fn (ImagePrompt $prompt) => $prompt->contains('Brazilian Portuguese'));
});

test('generate appends Spanish instruction when language is es', function () {
    Image::fake();

    $client = new AiImageClient;
    $client->generate(['x'], ImageStyle::Cinematic, language: 'es');

    Image::assertGenerated(fn (ImagePrompt $prompt) => $prompt->contains('Spanish'));
});

test('generate appends French instruction when language is fr', function () {
    Image::fake();

    $client = new AiImageClient;
    $client->generate(['x'], ImageStyle::Cinematic, language: 'fr');

    Image::assertGenerated(fn (ImagePrompt $prompt) => $prompt->contains('French'));
});

test('generate appends Ukrainian instruction when language is uk', function () {
    Image::fake();

    $client = new AiImageClient;
    $client->generate(['x'], ImageStyle::Cinematic, language: 'uk');

    Image::assertGenerated(fn (ImagePrompt $prompt) => $prompt->contains('Ukrainian'));
});

test('generate defaults to English instruction when language is unsupported', function () {
    Image::fake();

    $client = new AiImageClient;
    $client->generate(['x'], ImageStyle::Cinematic, language: 'sv');

    Image::assertGenerated(fn (ImagePrompt $prompt) => $prompt->contains('English'));
});

test('generate appends brand palette when workspace colours are provided', function () {
    Image::fake();

    $client = new AiImageClient;
    $client->generate(
        ['x'],
        ImageStyle::Infographic,
        brandColor: '#facc15',
        backgroundColor: '#ffffff',
        textColor: '#0f172a',
    );

    Image::assertGenerated(fn (ImagePrompt $prompt) => $prompt->contains('BRAND COLOR PALETTE')
        && $prompt->contains('golden yellow')
        && $prompt->contains('charts, bars')
        && $prompt->contains('off-white')
        && $prompt->contains('in-scene typography'));
});

test('generate includes an extended brand palette and visual guidance in the prompt', function () {
    Image::fake();

    $client = new AiImageClient;
    $client->generate(
        ['trainer', 'laptop'],
        ImageStyle::Cinematic,
        language: 'zh',
        brandColor: '#D6A928',
        backgroundColor: '#F7F3EA',
        textColor: '#292723',
        brandDescription: 'A trusted practical mentor.',
        extendedPalette: [
            'Warm Ivory' => '#F7F3EA',
            'Soft Mustard' => '#D6A928',
            'Warm Charcoal' => '#292723',
        ],
        visualNotes: 'Use refined editorial Traditional Chinese composition.',
        brandGuidelines: 'Human first, technology second.',
    );

    Image::assertGenerated(fn (ImagePrompt $prompt) => $prompt->contains('Warm Ivory')
        && $prompt->contains('#D6A928')
        && $prompt->contains('Traditional Chinese')
        && $prompt->contains('Human first, technology second.')
        && $prompt->contains('Chinese'));
});

test('generate omits brand palette when no workspace colours are set', function () {
    Image::fake();

    $client = new AiImageClient;
    $client->generate(['x'], ImageStyle::Cinematic);

    Image::assertGenerated(fn (ImagePrompt $prompt) => ! $prompt->contains('BRAND COLOR PALETTE'));
});

test('generate includes only valid colours in the palette', function () {
    Image::fake();

    $client = new AiImageClient;
    $client->generate(['x'], ImageStyle::Cinematic, brandColor: 'not-a-hex', backgroundColor: '#ffffff');

    Image::assertGenerated(fn (ImagePrompt $prompt) => $prompt->contains('BRAND COLOR PALETTE')
        && $prompt->contains('off-white')
        && ! $prompt->contains('Brand / primary accent'));
});

test('generate appends brand context when brandDescription is provided', function () {
    Image::fake();

    $client = new AiImageClient;
    $client->generate(['x'], ImageStyle::Cinematic, brandDescription: 'a fitness coaching brand for busy professionals');

    Image::assertGenerated(fn (ImagePrompt $prompt) => $prompt->contains('Brand context')
        && $prompt->contains('fitness coaching'));
});

test('generate truncates brand description longer than 200 chars', function () {
    Image::fake();

    $longDescription = str_repeat('lorem ipsum ', 50);
    $client = new AiImageClient;
    $client->generate(['x'], ImageStyle::Cinematic, brandDescription: $longDescription);

    Image::assertGenerated(fn (ImagePrompt $prompt) => $prompt->contains('Brand context')
        && $prompt->contains('…'));
});

test('generate omits brand context when brandDescription is empty or whitespace', function () {
    Image::fake();

    $client = new AiImageClient;
    $client->generate(['x'], ImageStyle::Cinematic, brandDescription: '   ');

    Image::assertGenerated(fn (ImagePrompt $prompt) => ! $prompt->contains('Brand context'));
});

test('generate returns null when SDK throws', function () {
    Image::fake(fn () => throw new RuntimeException('boom'));

    $client = new AiImageClient;

    expect($client->generate(['x'], ImageStyle::Cinematic))->toBeNull();
});

test('generate returns null instead of throwing when the provider responds with no images', function () {
    Image::fake(fn () => new ImageResponse(
        new Collection,
        new Usage,
        new Meta('openai', 'gpt-image-2'),
    ));

    $client = new AiImageClient;

    expect($client->generate(['x'], ImageStyle::Cinematic))->toBeNull();
});

test('generate retries a transient failure and succeeds on a later attempt', function () {
    config()->set('ai.image.max_attempts', 3);
    config()->set('ai.image.retry_delay_ms', 0);

    $bytes = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==');
    $attempts = 0;

    // Mirrors the prod fault: the first calls throw (BytePlus timeout / 5xx /
    // body the SDK can't parse -> TypeError), then the provider recovers.
    Image::fake(function () use (&$attempts, $bytes) {
        $attempts++;

        if ($attempts < 3) {
            throw new RuntimeException('cURL error 28: Operation timed out');
        }

        return base64_encode($bytes);
    });

    $client = new AiImageClient;
    $result = $client->generate(['kitchen'], ImageStyle::Cinematic);

    expect($attempts)->toBe(3)
        ->and($result)->not->toBeNull()
        ->and($result['bytes'])->toBe($bytes);
});

test('generate returns null after exhausting every retry attempt', function () {
    config()->set('ai.image.max_attempts', 3);
    config()->set('ai.image.retry_delay_ms', 0);

    $attempts = 0;
    Image::fake(function () use (&$attempts) {
        $attempts++;
        throw new RuntimeException('cURL error 28: Operation timed out');
    });

    $client = new AiImageClient;
    $result = $client->generate(['x'], ImageStyle::Cinematic);

    expect($result)->toBeNull()
        ->and($attempts)->toBe(3);
});

test('generate does not retry when max_attempts is 1', function () {
    config()->set('ai.image.max_attempts', 1);
    config()->set('ai.image.retry_delay_ms', 0);

    $attempts = 0;
    Image::fake(function () use (&$attempts) {
        $attempts++;
        throw new RuntimeException('boom');
    });

    $client = new AiImageClient;
    $result = $client->generate(['x'], ImageStyle::Cinematic);

    expect($result)->toBeNull()
        ->and($attempts)->toBe(1);
});

test('generate returns null when the provider keeps responding with no image', function () {
    config()->set('ai.image.max_attempts', 3);
    config()->set('ai.image.retry_delay_ms', 0);

    $attempts = 0;
    Image::fake(function () use (&$attempts) {
        $attempts++;

        return new ImageResponse(
            new Collection,
            new Usage,
            new Meta('openai', 'gpt-image-2'),
        );
    });

    $client = new AiImageClient;
    $result = $client->generate(['x'], ImageStyle::Cinematic);

    // An empty ImageResponse throws when the SDK casts it to bytes, so it is
    // indistinguishable from a transient fault and is retried; the important
    // guarantee is that the caller still gets null to fall back on.
    expect($result)->toBeNull()
        ->and($attempts)->toBe(3);
});

test('generate uses high resolution 2K sizes when BytePlus Seedream is configured', function (string $orientation, string $expectedSize) {
    config()->set('ai.providers.openai.models.image.default', 'seedream-4-5-251128');
    Image::fake();

    $client = new AiImageClient;
    $client->generate(['x'], ImageStyle::Cinematic, orientation: $orientation);

    Image::assertGenerated(fn (ImagePrompt $prompt) => $prompt->size === $expectedSize);
})->with([
    'square' => ['square', '2048x2048'],
    'portrait' => ['portrait', '1664x2496'],
    'landscape' => ['landscape', '2496x1664'],
]);

test('generate attaches reference images and adds subject consistency prompt instructions', function () {
    Storage::fake();
    Storage::put('medias/sara_reference.jpg', 'fake-image-data');

    Image::fake();

    $client = new AiImageClient;
    $client->generate(
        keywords: ['Sara presenting digital transformation'],
        style: ImageStyle::Cinematic,
        referenceImages: ['medias/sara_reference.jpg'],
    );

    Image::assertGenerated(function (ImagePrompt $prompt) {
        return count($prompt->attachments) === 1
            && $prompt->attachments[0]->path === 'medias/sara_reference.jpg'
            && $prompt->contains('SUBJECT & PERSONA CONSISTENCY')
            && $prompt->contains('Maintain faithful visual consistency with the subject');
    });
});

test('generate drops reference attachments for Seedream (text-to-image only, no images/edits support)', function () {
    // Seedream returns an empty 200 body on images/edits, which the SDK turns
    // into a TypeError. Reference photos must be dropped so the call stays on
    // images/generations and still produces an image.
    config()->set('ai.providers.openai.models.image.default', 'seedream-4-5-251128');

    Storage::fake();
    Storage::put('medias/ref.jpg', 'fake-image-data');

    Image::fake();

    $client = new AiImageClient;
    $result = $client->generate(
        keywords: ['office desk'],
        style: ImageStyle::Cinematic,
        referenceImages: ['medias/ref.jpg'],
    );

    expect($result)->not->toBeNull();
    Image::assertGenerated(fn (ImagePrompt $prompt) => $prompt->attachments->isEmpty()
        && ! $prompt->contains('SUBJECT & PERSONA CONSISTENCY'));
});

test('generate keeps reference attachments for an edit-capable provider', function () {
    Storage::fake();
    Storage::put('medias/ref.jpg', 'fake-image-data');

    Image::fake();

    $client = new AiImageClient;
    $client->generate(
        keywords: ['office desk'],
        style: ImageStyle::Cinematic,
        referenceImages: ['medias/ref.jpg'],
    );

    Image::assertGenerated(fn (ImagePrompt $prompt) => count($prompt->attachments) === 1);
});

test('ai.image.supports_edits config explicitly overrides provider inference', function () {
    config()->set('ai.image.supports_edits', false);

    Storage::fake();
    Storage::put('medias/ref.jpg', 'fake-image-data');

    Image::fake();

    $client = new AiImageClient;
    $client->generate(
        keywords: ['office desk'],
        style: ImageStyle::Cinematic,
        referenceImages: ['medias/ref.jpg'],
    );

    Image::assertGenerated(fn (ImagePrompt $prompt) => $prompt->attachments->isEmpty());
});

test('ai.image.supports_edits=true forces attachments even for Seedream', function () {
    config()->set('ai.providers.openai.models.image.default', 'seedream-4-5-251128');
    config()->set('ai.image.supports_edits', true);

    Storage::fake();
    Storage::put('medias/ref.jpg', 'fake-image-data');

    Image::fake();

    $client = new AiImageClient;
    $client->generate(
        keywords: ['office desk'],
        style: ImageStyle::Cinematic,
        referenceImages: ['medias/ref.jpg'],
    );

    Image::assertGenerated(fn (ImagePrompt $prompt) => count($prompt->attachments) === 1);
});
