<?php

declare(strict_types=1);

namespace App\Services\PostImport;

use App\Enums\PostImport\RowStatus;
use App\Enums\Workspace\ContentLanguage;

class PostImportParser
{
    public const MAX_ROWS = 50;

    /**
     * @var array<int, string>
     */
    private const IDEA_COLUMNS = ['topic', 'content', 'description'];

    /**
     * @var array<string, string>
     */
    private const BRIEF_LABELS = [
        'topic' => 'Topic',
        'content_pillar' => 'Content pillar',
        'key_insight' => 'Key insight',
        'my_judgement' => 'My judgement',
        'supporting_information' => 'Supporting info',
        'business_context' => 'Business context',
        'target_audience' => 'Target audience',
        'desired_takeaway' => 'Desired takeaway',
        'tone' => 'Tone',
        'content_goal' => 'Goal',
        'cta_direction' => 'CTA',
        'visual_direction' => 'Visual direction',
        'description' => 'Description',
        'content' => 'Content',
    ];

    /**
     * @var array<int, string>
     */
    private const COMPLETENESS_KEYS = ['topic', 'key_insight', 'target_audience', 'tone', 'content_goal'];

    private const COMPLETENESS_THRESHOLD = 3;

    /**
     * @return array{header: array<int, string>, rows: array<int, array{
     *     row_number: int,
     *     raw: array<string, string>,
     *     brief: array<string, string>,
     *     mapped_content: ?string,
     *     language_code: ?string,
     *     status: RowStatus,
     *     error: ?string,
     * }>, truncated: bool}
     */
    public function parse(string $path): array
    {
        $handle = fopen($path, 'r');

        if ($handle === false) {
            return ['header' => [], 'rows' => [], 'truncated' => false];
        }

        try {
            $header = $this->readHeader($handle);

            if ($header === []) {
                return ['header' => [], 'rows' => [], 'truncated' => false];
            }

            $rows = [];
            $rowNumber = 0;
            $truncated = false;

            while (($record = fgetcsv($handle)) !== false) {
                if ($this->isBlank($record)) {
                    continue;
                }

                $rowNumber++;

                if ($rowNumber > self::MAX_ROWS) {
                    $truncated = true;
                    break;
                }

                $rows[] = $this->mapRow($header, $record, $rowNumber);
            }

            return ['header' => $header, 'rows' => $rows, 'truncated' => $truncated];
        } finally {
            fclose($handle);
        }
    }

    /**
     * @param  resource  $handle
     * @return array<int, string>
     */
    private function readHeader($handle): array
    {
        $header = fgetcsv($handle);

        if ($header === false) {
            return [];
        }

        return array_map(
            fn (?string $column): string => $this->normalizeKey((string) $column),
            $header,
        );
    }

    /**
     * @param  array<int, string>  $header
     * @param  array<int, ?string>  $record
     * @return array{
     *     row_number: int,
     *     raw: array<string, string>,
     *     brief: array<string, string>,
     *     mapped_content: ?string,
     *     language_code: ?string,
     *     status: RowStatus,
     *     error: ?string,
     * }
     */
    private function mapRow(array $header, array $record, int $rowNumber): array
    {
        $raw = [];

        foreach ($header as $index => $key) {
            if ($key === '') {
                continue;
            }

            $raw[$key] = trim((string) ($record[$index] ?? ''));
        }

        $brief = [];

        foreach (self::BRIEF_LABELS as $key => $label) {
            if (($raw[$key] ?? '') !== '') {
                $brief[$key] = $raw[$key];
            }
        }

        $languageCode = null;

        if (($raw['language'] ?? '') !== '') {
            $languageCode = ContentLanguage::fromNameOrCode($raw['language'])?->value;
        }

        $hasIdea = false;

        foreach (self::IDEA_COLUMNS as $column) {
            if (($raw[$column] ?? '') !== '') {
                $hasIdea = true;

                break;
            }
        }

        if (! $hasIdea) {
            return [
                'row_number' => $rowNumber,
                'raw' => $raw,
                'brief' => $brief,
                'mapped_content' => null,
                'language_code' => $languageCode,
                'status' => RowStatus::Invalid,
                'error' => 'missing_idea',
            ];
        }

        $filledKeys = array_intersect(self::COMPLETENESS_KEYS, array_keys($brief));
        $status = count($filledKeys) >= self::COMPLETENESS_THRESHOLD
            ? RowStatus::Valid
            : RowStatus::NeedsReview;

        return [
            'row_number' => $rowNumber,
            'raw' => $raw,
            'brief' => $brief,
            'mapped_content' => $this->mapContent($brief, $languageCode),
            'language_code' => $languageCode,
            'status' => $status,
            'error' => null,
        ];
    }

    /**
     * @param  array<string, string>  $brief
     */
    private function mapContent(array $brief, ?string $languageCode): string
    {
        $lines = [];

        foreach (self::BRIEF_LABELS as $key => $label) {
            if (($brief[$key] ?? '') !== '') {
                $lines[] = "{$label}: {$brief[$key]}";
            }
        }

        if ($languageCode !== null) {
            $lines[] = 'Language: '.$languageCode;
        }

        return implode("\n", $lines);
    }

    private function normalizeKey(string $value): string
    {
        return strtolower(trim(preg_replace('/^\xEF\xBB\xBF/', '', $value) ?? $value));
    }

    /**
     * @param  array<int, ?string>  $record
     */
    private function isBlank(array $record): bool
    {
        foreach ($record as $value) {
            if (trim((string) $value) !== '') {
                return false;
            }
        }

        return true;
    }
}
