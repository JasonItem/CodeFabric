<?php

declare(strict_types=1);

namespace Tests\Unit\Enums;

use App\Enums\ApiCode;
use PHPUnit\Framework\TestCase;

final class ApiCodeTest extends TestCase
{
    public function test_from_http_status_maps_correctly(): void
    {
        $this->assertSame(ApiCode::UNAUTHORIZED, ApiCode::fromHttpStatus(401));
        $this->assertSame(ApiCode::FORBIDDEN, ApiCode::fromHttpStatus(403));
        $this->assertSame(ApiCode::NOT_FOUND, ApiCode::fromHttpStatus(404));
        $this->assertSame(ApiCode::CONFLICT, ApiCode::fromHttpStatus(409));
        $this->assertSame(ApiCode::VALIDATION_ERROR, ApiCode::fromHttpStatus(422));
        $this->assertSame(ApiCode::REPEAT_SUBMIT, ApiCode::fromHttpStatus(429));
        $this->assertSame(ApiCode::SERVER_ERROR, ApiCode::fromHttpStatus(500));
        $this->assertSame(ApiCode::BAD_REQUEST, ApiCode::fromHttpStatus(400));
    }
}

