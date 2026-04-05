<?php

declare(strict_types=1);

namespace App\Modules\Admin\Auth\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * 修改密码请求参数。
 */
final class ChangePasswordRequest extends FormRequest
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
            'oldPassword' => ['required', 'string', 'min:6', 'max:100'],
            'newPassword' => ['required', 'string', 'min:6', 'max:100', 'different:oldPassword'],
        ];
    }
}

