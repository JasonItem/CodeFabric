<?php

declare(strict_types=1);

namespace App\Modules\Admin\Menu\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class CreateMenuRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:100'],
            'path' => ['nullable', 'string', 'max:200'],
            'component' => ['nullable', 'string', 'max:200'],
            'icon' => ['nullable', 'string', 'max:100'],
            'type' => ['required', Rule::in(['DIRECTORY', 'MENU', 'BUTTON'])],
            'permissionKey' => ['nullable', 'string', 'max:120'],
            'sort' => ['required', 'integer', 'min:0'],
            'visible' => ['required', 'boolean'],
        ];
    }
}

