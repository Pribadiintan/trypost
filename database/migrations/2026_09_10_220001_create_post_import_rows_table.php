<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('post_import_rows', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('post_import_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('row_number');
            $table->json('raw');
            $table->json('brief')->nullable();
            $table->text('mapped_content')->nullable();
            $table->string('language_code', 20)->nullable();
            $table->string('status')->default('valid');
            $table->string('error', 500)->nullable();
            $table->foreignUuid('post_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();

            $table->index(['post_import_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('post_import_rows');
    }
};
