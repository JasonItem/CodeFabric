<?php

declare(strict_types=1);

namespace App\Modules\Admin\Role\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateRoleRequest extends FormRequest
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
            'name' => ['sometimes', 'string', 'max:50'],
            'code' => ['sometimes', 'string', 'max:50'],
            'description' => ['nullable', 'string', 'max:255'],
        ];
    }
}

