<?php

declare(strict_types=1);

namespace App\Modules\Admin\LoginLog\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class ListLoginLogRequest extends FormRequest
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
            'page' => ['required', 'integer', 'min:1'],
            'pageSize' => ['required', 'integer', 'min:1', 'max:200'],
            'ip' => ['nullable', 'string', 'max:100'],
            'username' => ['nullable', 'string', 'max:100'],
            'success' => ['nullable', 'in:true,false,1,0'],
            'startTime' => ['nullable', 'date'],
            'endTime' => ['nullable', 'date'],
        ];
    }
}
