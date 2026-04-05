<?php

declare(strict_types=1);

namespace App\Modules\Admin\File\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class ListFilesRequest extends FormRequest
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
            'page' => ['nullable', 'integer', 'min:1'],
            'pageSize' => ['nullable', 'integer', 'min:1', 'max:200'],
            'folderId' => ['nullable', 'integer', 'min:1'],
            'keyword' => ['nullable', 'string', 'max:255'],
            'source' => ['nullable', 'in:ADMIN,USER'],
            'kind' => ['nullable', 'in:IMAGE,VIDEO,FILE'],
            'startAt' => ['nullable', 'date'],
            'endAt' => ['nullable', 'date'],
        ];
    }
}

