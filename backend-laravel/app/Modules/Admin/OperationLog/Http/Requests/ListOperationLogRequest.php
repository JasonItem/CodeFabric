<?php

declare(strict_types=1);

namespace App\Modules\Admin\OperationLog\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class ListOperationLogRequest extends FormRequest
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
            'path' => ['nullable', 'string', 'max:255'],
            'module' => ['nullable', 'string', 'max:100'],
            'username' => ['nullable', 'string', 'max:100'],
            'success' => ['nullable', 'in:true,false,1,0'],
            'startTime' => ['nullable', 'date'],
            'endTime' => ['nullable', 'date'],
        ];
    }
}

