<?php

declare(strict_types=1);

namespace App\Modules\Admin\User\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class CreateUserRequest extends FormRequest
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
            'username' => ['required', 'string', 'max:50'],
            'nickname' => ['required', 'string', 'max:100'],
            'password' => ['required', 'string', 'min:6', 'max:100'],
            'status' => ['required', Rule::in(['ACTIVE', 'DISABLED'])],
            'roleIds' => ['array'],
            'roleIds.*' => ['integer', 'min:1', 'exists:Role,id'],
        ];
    }
}
