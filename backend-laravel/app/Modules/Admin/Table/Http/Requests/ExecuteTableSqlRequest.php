<?php

declare(strict_types=1);

namespace App\Modules\Admin\Table\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class ExecuteTableSqlRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'sql' => ['required', 'string'],
        ];
    }
}

