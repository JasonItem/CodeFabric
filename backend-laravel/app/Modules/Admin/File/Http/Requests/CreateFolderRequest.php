<?php

declare(strict_types=1);

namespace App\Modules\Admin\File\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class CreateFolderRequest extends FormRequest
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
            'parentId' => ['nullable', 'integer', 'min:1'],
            'name' => ['required', 'string', 'max:120'],
            'sort' => ['nullable', 'integer', 'min:0'],
        ];
    }
}

