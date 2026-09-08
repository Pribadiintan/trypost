<?php

declare(strict_types=1);

$langBase = dirname(__DIR__, 3).'/lang';

$locales = array_values(array_filter(
    array_map('basename', glob($langBase.'/*') ?: []),
    fn (string $locale): bool => is_dir("{$langBase}/{$locale}") && file_exists("{$langBase}/{$locale}/posts.php"),
));

dataset('wizard_locales', $locales);

it('defines all required wizard translation keys inside the wizard array', function (string $locale): void {
    $file = dirname(__DIR__, 3)."/lang/{$locale}/posts.php";
    expect(file_exists($file))->toBeTrue();

    $posts = require $file;
    expect($posts)->toBeArray()
        ->and($posts)->toHaveKey('wizard');

    $wizard = $posts['wizard'];
    expect($wizard)->toBeArray();

    $requiredKeys = [
        'prompt_label',
        'prompt_placeholder',
        'format_label',
        'account_label',
        'style_label',
        'images_label',
        'brand_colors_label',
        'brand_references_label',
        'language_label',
        'generate',
        'failed',
        'detached',
        'connect_first',
        'connect_cta',
        'generating_text',
        'text_ready',
        'submitting',
        'check_status',
        'status_check_failed',
        'credits_exhausted',
        'brand_references_select_all',
        'brand_references_clear',
    ];

    foreach ($requiredKeys as $key) {
        expect($wizard)->toHaveKey($key);
        expect($wizard[$key])->toBeString();
        expect(trim($wizard[$key]))->not->toBeEmpty();
    }
})->with('wizard_locales');

it('does not leak wizard keys to the root posts array', function (string $locale): void {
    $file = dirname(__DIR__, 3)."/lang/{$locale}/posts.php";
    $posts = require $file;

    $disallowedRootKeys = [
        'prompt_label',
        'prompt_placeholder',
        'format_label',
        'account_label',
        'style_label',
        'images_label',
        'brand_colors_label',
        'brand_references_label',
        'language_label',
        'generate',
        'failed',
        'detached',
        'brand_references_select_all',
        'brand_references_clear',
    ];

    foreach ($disallowedRootKeys as $key) {
        expect($posts)->not->toHaveKey($key);
    }
})->with('wizard_locales');
