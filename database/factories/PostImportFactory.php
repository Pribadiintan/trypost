<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\PostImport\Status;
use App\Models\PostImport;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PostImport>
 */
class PostImportFactory extends Factory
{
    protected $model = PostImport::class;

    public function definition(): array
    {
        return [
            'workspace_id' => Workspace::factory(),
            'user_id' => User::factory(),
            'original_filename' => $this->faker->slug().'.csv',
            'path' => 'post-imports/'.$this->faker->uuid().'.csv',
            'status' => Status::Parsing,
            'total_rows' => 0,
            'valid_rows' => 0,
            'invalid_rows' => 0,
            'created_count' => 0,
        ];
    }

    public function previewReady(): self
    {
        return $this->state(fn (): array => ['status' => Status::PreviewReady]);
    }
}
