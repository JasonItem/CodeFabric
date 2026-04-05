<?php

declare(strict_types=1);

namespace App\Modules\Admin\File\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;

final class UploadFilesRequest extends FormRequest
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
        $extensions = config('upload.allowed_extensions', []);
        $mimesRule = empty($extensions) ? null : ('mimes:'.implode(',', $extensions));

        return [
            'files' => [
                'required',
                function (string $attribute, mixed $value, \Closure $fail) use ($extensions): void {
                    $files = $this->normalizedFiles();
                    if (empty($files)) {
                        $fail('请选择至少一个上传文件');
                        return;
                    }

                    if (!empty($extensions)) {
                        foreach ($files as $file) {
                            $ext = strtolower((string) $file->getClientOriginalExtension());
                            if ($ext === '' || !in_array($ext, $extensions, true)) {
                                $fail('文件类型不允许上传');
                                return;
                            }
                        }
                    }
                },
            ],
            'files.*' => array_values(array_filter([
                'required',
                'file',
                'max:102400',
                $mimesRule,
            ])),
            'folderId' => ['nullable', 'integer', 'min:1'],
            'source' => ['nullable', 'in:ADMIN,USER'],
        ];
    }

    public function messages(): array
    {
        return [
            'files.required' => '请选择至少一个上传文件',
            'files.*.file' => '上传内容必须是文件',
            'files.*.max' => '单个文件大小不能超过 100MB',
            'files.*.mimes' => '文件类型不允许上传',
            'folderId.integer' => '分组参数不合法',
            'folderId.min' => '分组参数不合法',
            'source.in' => '文件来源参数不合法',
        ];
    }

    /**
     * 统一获取上传文件数组，兼容 files / files[] / 单文件场景。
     *
     * @return array<int,UploadedFile>
     */
    public function normalizedFiles(): array
    {
        $candidates = $this->file('files');

        if ($candidates instanceof UploadedFile) {
            return [$candidates];
        }

        if (is_array($candidates)) {
            return array_values(array_filter($candidates, static fn (mixed $file): bool => $file instanceof UploadedFile));
        }

        $single = $this->file('file');
        if ($single instanceof UploadedFile) {
            return [$single];
        }

        return [];
    }
}
