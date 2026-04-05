<?php

declare(strict_types=1);

namespace App\Modules\Admin\File\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateFileRequest extends FormRequest
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
            'folderId' => ['nullable', 'integer', 'min:1'],
            'name' => ['sometimes', 'string', 'max:255'],
        ];
    }
}

