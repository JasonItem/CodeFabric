<?php

declare(strict_types=1);

namespace App\Modules\Admin\Auth\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * 登录请求参数。
 */
final class LoginRequest extends FormRequest
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
            'password' => ['required', 'string', 'min:6', 'max:100'],
        ];
    }
}

