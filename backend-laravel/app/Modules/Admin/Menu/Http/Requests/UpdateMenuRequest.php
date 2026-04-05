<?php

declare(strict_types=1);

namespace App\Modules\Admin\Menu\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateMenuRequest extends FormRequest
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
            'parentId' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'name' => ['sometimes', 'string', 'max:100'],
            'path' => ['sometimes', 'nullable', 'string', 'max:200'],
            'component' => ['sometimes', 'nullable', 'string', 'max:200'],
            'icon' => ['sometimes', 'nullable', 'string', 'max:100'],
            'type' => ['sometimes', Rule::in(['DIRECTORY', 'MENU', 'BUTTON'])],
            'permissionKey' => ['sometimes', 'nullable', 'string', 'max:120'],
            'sort' => ['sometimes', 'integer', 'min:0'],
            'visible' => ['sometimes', 'boolean'],
        ];
    }
}

