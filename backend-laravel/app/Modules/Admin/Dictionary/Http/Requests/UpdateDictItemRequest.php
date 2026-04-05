<?php

declare(strict_types=1);

namespace App\Modules\Admin\Dictionary\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateDictItemRequest extends FormRequest
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
            'label' => ['sometimes', 'string', 'max:100'],
            'value' => ['sometimes', 'string', 'max:100'],
            'tagType' => ['sometimes', 'nullable', 'string', 'max:50'],
            'tagClass' => ['sometimes', 'nullable', 'string', 'max:100'],
            'status' => ['sometimes', 'boolean'],
            'sort' => ['sometimes', 'integer', 'min:0'],
        ];
    }
}
