<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Admin\File\Infrastructure\Persistence\Repositories;

use App\Modules\Admin\File\Infrastructure\Persistence\Mappers\StoredFileMapper;
use App\Modules\Admin\File\Infrastructure\Persistence\Repositories\EloquentStoredFileRepository;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class EloquentStoredFileRepositoryTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropIfExists('StoredFile');
        Schema::dropIfExists('FileFolder');

        Schema::create('FileFolder', function (Blueprint $table): void {
            $table->increments('id');
            $table->unsignedInteger('parentId')->nullable();
            $table->string('name', 100);
            $table->integer('sort')->default(0);
            $table->timestamp('createdAt')->nullable();
            $table->timestamp('updatedAt')->nullable();
        });

        Schema::create('StoredFile', function (Blueprint $table): void {
            $table->increments('id');
            $table->unsignedInteger('folderId')->nullable();
            $table->string('source', 20);
            $table->string('kind', 20);
            $table->string('name', 255);
            $table->string('originalName', 255);
            $table->string('ext', 20)->nullable();
            $table->string('mimeType', 120)->nullable();
            $table->unsignedBigInteger('size')->default(0);
            $table->string('relativePath', 255);
            $table->string('url', 255);
            $table->unsignedInteger('createdById')->nullable();
            $table->string('createdByName', 100)->nullable();
            $table->timestamp('createdAt')->nullable();
            $table->timestamp('updatedAt')->nullable();
        });
    }

    #[Test]
    public function keyword_or_condition_is_grouped_and_does_not_break_other_filters(): void
    {
        DB::table('StoredFile')->insert([
            [
                'folderId' => 1,
                'source' => 'ADMIN',
                'kind' => 'IMAGE',
                'name' => 'alpha-report',
                'originalName' => 'alpha-report.png',
                'ext' => 'png',
                'mimeType' => 'image/png',
                'size' => 1,
                'relativePath' => 'uploads/1.png',
                'url' => '/storage/uploads/1.png',
                'createdAt' => now(),
                'updatedAt' => now(),
            ],
            [
                'folderId' => 1,
                'source' => 'USER',
                'kind' => 'IMAGE',
                'name' => 'zeta',
                'originalName' => 'alpha-report-user.png',
                'ext' => 'png',
                'mimeType' => 'image/png',
                'size' => 1,
                'relativePath' => 'uploads/2.png',
                'url' => '/storage/uploads/2.png',
                'createdAt' => now(),
                'updatedAt' => now(),
            ],
        ]);

        $repo = new EloquentStoredFileRepository(new StoredFileMapper());
        $result = $repo->paginate([
            'page' => 1,
            'pageSize' => 20,
            'source' => 'ADMIN',
            'keyword' => 'alpha-report',
        ]);

        $this->assertSame(1, $result['total']);
        $this->assertCount(1, $result['list']);
        $this->assertSame('ADMIN', $result['list'][0]['source']);
    }
}

