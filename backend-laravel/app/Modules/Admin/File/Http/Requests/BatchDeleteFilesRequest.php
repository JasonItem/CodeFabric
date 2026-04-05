<?php

declare(strict_types=1);

namespace App\Modules\Admin\File\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class BatchDeleteFilesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string,mixed>
     */
    public function rules(): array
    {
        return [
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['required', 'integer', 'min:1'],
        ];
    }
}

