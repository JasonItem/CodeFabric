<?php

declare(strict_types=1);

namespace App\Modules\Admin\Dictionary\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class CreateDictTypeRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:100'],
            'code' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:255'],
            'status' => ['required', 'boolean'],
            'sort' => ['required', 'integer', 'min:0'],
        ];
    }
}
