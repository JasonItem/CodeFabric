<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | 允许上传的扩展名白名单
    |--------------------------------------------------------------------------
    | 默认覆盖图片 / 视频 / 常见文档，且默认禁用 html/js/svg 等高风险类型。
    */
    'allowed_extensions' => array_filter(array_map(
        static fn (string $value): string => strtolower(trim($value)),
        explode(',', (string) env('UPLOAD_ALLOWED_EXTENSIONS', 'jpg,jpeg,png,gif,webp,bmp,mp4,mov,avi,mkv,webm,m4v,pdf,doc,docx,xls,xlsx,ppt,pptx,txt,md,csv,zip,rar,7z,json,xml'))
    )),
];

