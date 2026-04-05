<?php

declare(strict_types=1);

namespace App\Modules\Admin\LoginLog\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class ClearLoginLogRequest extends FormRequest
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
            'ids' => ['sometimes', 'array'],
            'ids.*' => ['integer', 'min:1'],
        ];
    }
}
