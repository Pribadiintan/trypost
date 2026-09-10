<?php

declare(strict_types=1);

use App\Ai\Agents\Concerns\AiTimeouts;
use App\Ai\Agents\PostCaptionRegenerator;
use App\Ai\Agents\PostContentGenerator;
use App\Ai\Agents\PostContentHumanizer;
use App\Ai\Agents\PostContentStreamer;
use App\Ai\Agents\PostImageRegenerator;
use Laravel\Ai\Attributes\Timeout;

/**
 * Every text-generation agent must declare an explicit #[Timeout]. Without one
 * the Laravel AI SDK falls back to the HTTP client's 60s default, which was the
 * root cause of `cURL error 28: Operation timed out after 60002ms` against slow
 * DeepSeek responses in production. PostCaptionRegenerator shipped without the
 * attribute and its regenerate-caption turn silently failed at 60s; this guards
 * every agent on that path so the gap cannot reopen.
 */
function timeoutSeconds(string $agentClass): ?int
{
    $attributes = (new ReflectionClass($agentClass))->getAttributes(Timeout::class);

    if ($attributes === []) {
        return null;
    }

    return $attributes[0]->newInstance()->value;
}

it('declares the shared text timeout on every text-generation agent', function (string $agentClass): void {
    expect(timeoutSeconds($agentClass))->toBe(AiTimeouts::TEXT_SECONDS);
})->with([
    PostCaptionRegenerator::class,
    PostContentGenerator::class,
    PostContentHumanizer::class,
    PostContentStreamer::class,
    PostImageRegenerator::class,
]);
