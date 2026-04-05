<?php

declare(strict_types=1);

namespace App\Modules\Admin\User\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateUserRequest extends FormRequest
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
            'username' => ['sometimes', 'string', 'max:50'],
            'nickname' => ['sometimes', 'string', 'max:100'],
            'password' => ['sometimes', 'nullable', 'string', 'min:6', 'max:100'],
            'status' => ['sometimes', Rule::in(['ACTIVE', 'DISABLED'])],
            'roleIds' => ['sometimes', 'array'],
            'roleIds.*' => ['integer', 'min:1', 'exists:Role,id'],
        ];
    }
}
