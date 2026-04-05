<?php

declare(strict_types=1);

namespace App\Modules\Admin\Dictionary\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class CreateDictItemRequest extends FormRequest
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
            'label' => ['required', 'string', 'max:100'],
            'value' => ['required', 'string', 'max:100'],
            'tagType' => ['nullable', 'string', 'max:50'],
            'tagClass' => ['nullable', 'string', 'max:100'],
            'status' => ['required', 'boolean'],
            'sort' => ['required', 'integer', 'min:0'],
        ];
    }
}
