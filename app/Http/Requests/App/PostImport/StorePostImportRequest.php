<?php

declare(strict_types=1);

namespace App\Http\Requests\App\PostImport;

use Illuminate\Foundation\Http\FormRequest;

class StorePostImportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:5120'],
        ];
    }
}
