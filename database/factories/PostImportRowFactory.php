<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\PostImport\RowStatus;
use App\Models\PostImport;
use App\Models\PostImportRow;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PostImportRow>
 */
class PostImportRowFactory extends Factory
{
    protected $model = PostImportRow::class;

    public function definition(): array
    {
        $topic = $this->faker->sentence();

        return [
            'post_import_id' => PostImport::factory(),
            'row_number' => $this->faker->unique()->numberBetween(1, 500),
            'raw' => ['topic' => $topic],
            'brief' => ['topic' => $topic],
            'mapped_content' => 'Topic: '.$topic,
            'language_code' => null,
            'status' => RowStatus::Valid,
            'error' => null,
            'post_id' => null,
        ];
    }
}
