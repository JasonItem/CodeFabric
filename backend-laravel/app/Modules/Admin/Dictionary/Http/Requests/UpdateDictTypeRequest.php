<?php

declare(strict_types=1);

namespace App\Modules\Admin\Dictionary\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateDictTypeRequest extends FormRequest
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
            'name' => ['sometimes', 'string', 'max:100'],
            'code' => ['sometimes', 'string', 'max:100'],
            'description' => ['sometimes', 'nullable', 'string', 'max:255'],
            'status' => ['sometimes', 'boolean'],
            'sort' => ['sometimes', 'integer', 'min:0'],
        ];
    }
}
