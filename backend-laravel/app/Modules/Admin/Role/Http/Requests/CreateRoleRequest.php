<?php

declare(strict_types=1);

namespace App\Modules\Admin\Role\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class CreateRoleRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:50'],
            'code' => ['required', 'string', 'max:50'],
            'description' => ['nullable', 'string', 'max:255'],
        ];
    }
}

