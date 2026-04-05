<?php

declare(strict_types=1);

namespace App\Enums;

enum ApiCode: int
{
    case SUCCESS = 200;

    case BAD_REQUEST = 400;
    case UNAUTHORIZED = 401;
    case FORBIDDEN = 403;
    case NOT_FOUND = 404;
    case CONFLICT = 409;
    case VALIDATION_ERROR = 422;
    case REPEAT_SUBMIT = 429;

    case SERVER_ERROR = 500;

    public static function fromHttpStatus(int $status): self
    {
        return match (true) {
            $status === 200 => self::SUCCESS,
            $status === 401 => self::UNAUTHORIZED,
            $status === 403 => self::FORBIDDEN,
            $status === 404 => self::NOT_FOUND,
            $status === 409 => self::CONFLICT,
            $status === 422 => self::VALIDATION_ERROR,
            $status === 429 => self::REPEAT_SUBMIT,
            $status >= 500 => self::SERVER_ERROR,
            default => self::BAD_REQUEST,
        };
    }
}
