<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Admin\File;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Feature\Modules\Admin\AdminApiFeatureTestCase;

final class FileApiTest extends AdminApiFeatureTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
    }

    public function test_upload_requires_login(): void
    {
        $response = $this->post('/api/admin/files/upload', [
            'files' => UploadedFile::fake()->create('avatar.png', 10, 'image/png'),
        ]);

        $response->assertStatus(401);
        $response->assertJsonPath('message', '未登录或登录已失效');
    }

    public function test_admin_can_upload_file(): void
    {
        $token = $this->loginAndGetToken();
        $folderId = (int) \DB::table('FileFolder')->where('name', '演示图片')->value('id');

        $response = $this->withHeaders($this->authHeaders($token))
            ->post('/api/admin/files/upload', [
                'files' => UploadedFile::fake()->create('guide.pdf', 12, 'application/pdf'),
                'folderId' => $folderId,
                'source' => 'ADMIN',
            ]);

        $response->assertOk();
        $response->assertJsonPath('data.0.originalName', 'guide.pdf');
        $response->assertJsonPath('data.0.folderId', $folderId);

        $relativePath = (string) $response->json('data.0.relativePath');
        Storage::disk('public')->assertExists($relativePath);
        $this->assertDatabaseHas('StoredFile', [
            'originalName' => 'guide.pdf',
            'folderId' => $folderId,
        ]);
    }

    public function test_upload_rejects_disallowed_extension(): void
    {
        $token = $this->loginAndGetToken();

        $response = $this->withHeaders($this->authHeaders($token))
            ->post('/api/admin/files/upload', [
                'files' => UploadedFile::fake()->create('xss.svg', 10, 'image/svg+xml'),
            ]);

        $response->assertStatus(422);
        $response->assertJsonPath('message', '文件类型不允许上传');
    }

    public function test_delete_folder_with_files_requires_move_target(): void
    {
        $token = $this->loginAndGetToken();
        $folderId = (int) \DB::table('FileFolder')->where('name', '演示图片')->value('id');

        $this->withHeaders($this->authHeaders($token))
            ->post('/api/admin/files/upload', [
                'files' => UploadedFile::fake()->create('guide.pdf', 12, 'application/pdf'),
                'folderId' => $folderId,
                'source' => 'ADMIN',
            ])
            ->assertOk();

        $response = $this->withHeaders($this->authHeaders($token))
            ->delete("/api/admin/files/folders/{$folderId}");

        $response->assertStatus(400);
        $response->assertJsonPath('message', '分组下存在文件，请先选择目标分组');
    }
}
