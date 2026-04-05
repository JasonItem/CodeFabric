<?php

declare(strict_types=1);

namespace App\Modules\Admin\Role\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class AssignPermissionsRequest extends FormRequest
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
            'menuIds' => ['required', 'array'],
            'menuIds.*' => ['integer', 'min:1', 'exists:Menu,id'],
        ];
    }
}
