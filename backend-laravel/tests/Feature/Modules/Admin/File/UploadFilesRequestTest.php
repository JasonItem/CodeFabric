<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Admin\File;

use App\Modules\Admin\File\Http\Requests\UploadFilesRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class UploadFilesRequestTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Route::middleware('api')->post('/_test/upload-files', function (UploadFilesRequest $request): JsonResponse {
            return response()->json([
                'count' => count($request->normalizedFiles()),
            ]);
        });
    }

    #[Test]
    public function it_accepts_single_file_upload(): void
    {
        $response = $this->post('/_test/upload-files', [
            'files' => UploadedFile::fake()->create('avatar.png', 10, 'image/png'),
        ]);

        $response->assertOk();
        $response->assertJson(['count' => 1]);
    }

    #[Test]
    public function it_rejects_disallowed_extension(): void
    {
        $response = $this->post('/_test/upload-files', [
            'files' => UploadedFile::fake()->create('xss.svg', 10, 'image/svg+xml'),
        ]);

        $response->assertStatus(422);
        $response->assertJsonPath('code', 422);
        $response->assertJsonPath('message', '文件类型不允许上传');
    }
}
