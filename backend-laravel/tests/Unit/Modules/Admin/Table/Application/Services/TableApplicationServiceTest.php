<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Admin\Table\Application\Services;

use App\Enums\ApiCode;
use App\Modules\Admin\Table\Application\Services\TableApplicationService;
use App\Modules\Admin\Table\Domain\Contracts\TableRepositoryInterface;
use App\Shared\Exceptions\ApiBusinessException;
use Illuminate\Http\UploadedFile;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class TableApplicationServiceTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    #[Test]
    public function columns_rejects_invalid_table_name(): void
    {
        $repository = Mockery::mock(TableRepositoryInterface::class);
        $repository->shouldNotReceive('tableExists');

        $service = new TableApplicationService($repository);

        try {
            $service->columns('bad-name;drop');
            $this->fail('Expected ApiBusinessException was not thrown.');
        } catch (ApiBusinessException $e) {
            $this->assertSame(ApiCode::BAD_REQUEST, $e->apiCode);
            $this->assertSame('无效的数据表名称', $e->getMessage());
        }
    }

    #[Test]
    public function create_sql_returns_named_payload_when_repository_has_sql(): void
    {
        $repository = Mockery::mock(TableRepositoryInterface::class);
        $repository->shouldReceive('getCreateSql')->once()->with('users')->andReturn('CREATE TABLE `users` (`id` bigint)');

        $service = new TableApplicationService($repository);
        $result = $service->createSql('users');

        $this->assertSame('users', $result['tableName']);
        $this->assertStringContainsString('CREATE TABLE `users`', $result['createSql']);
    }

    #[Test]
    public function export_builds_sql_with_insert_statements(): void
    {
        $repository = Mockery::mock(TableRepositoryInterface::class);
        $repository->shouldReceive('getCreateSql')->once()->with('users')->andReturn('CREATE TABLE `users` (`id` bigint, `name` varchar(255))');
        $repository->shouldReceive('getTableData')->once()->with('users')->andReturn([
            ['id' => 1, 'name' => "O'Hara"],
        ]);

        $service = new TableApplicationService($repository);
        $result = $service->export('users');

        $this->assertSame('users.sql', $result['fileName']);
        $this->assertStringContainsString('CREATE TABLE `users`', $result['sql']);
        $this->assertStringContainsString("INSERT INTO `users` (`id`, `name`) VALUES (1, 'O\\'Hara');", $result['sql']);
    }

    #[Test]
    public function import_sql_file_in_skip_create_mode_skips_existing_tables(): void
    {
        $file = UploadedFile::fake()->createWithContent('schema.sql', <<<SQL
        CREATE TABLE `users` (`id` bigint);
        INSERT INTO `users` (`id`) VALUES (1);
        SQL);

        $repository = Mockery::mock(TableRepositoryInterface::class);
        $repository->shouldReceive('tableExists')->once()->with('users')->andReturn(true);
        $repository->shouldReceive('executeSql')->once()->with('INSERT INTO `users` (`id`) VALUES (1)');

        $service = new TableApplicationService($repository);
        $result = $service->importSqlFile($file, 'skip-create');

        $this->assertSame(1, $result['count']);
        $this->assertSame(1, $result['skippedCount']);
        $this->assertSame(['users'], $result['skippedTables']);
        $this->assertSame('skip-create', $result['mode']);
    }

    #[Test]
    public function import_sql_file_rejects_disallowed_statement(): void
    {
        $file = UploadedFile::fake()->createWithContent('bad.sql', 'DELETE FROM users WHERE id = 1;');

        $repository = Mockery::mock(TableRepositoryInterface::class);
        $repository->shouldNotReceive('executeSql');

        $service = new TableApplicationService($repository);

        try {
            $service->importSqlFile($file, 'strict');
            $this->fail('Expected ApiBusinessException was not thrown.');
        } catch (ApiBusinessException $e) {
            $this->assertSame(ApiCode::BAD_REQUEST, $e->apiCode);
            $this->assertStringContainsString('导入 SQL 含不允许语句', $e->getMessage());
        }
    }

    #[Test]
    public function create_by_sql_rejects_multiple_statements(): void
    {
        $repository = Mockery::mock(TableRepositoryInterface::class);
        $repository->shouldNotReceive('executeSql');

        $service = new TableApplicationService($repository);

        try {
            $service->createBySql("CREATE TABLE `users` (`id` bigint); DROP TABLE `logs`;");
            $this->fail('Expected ApiBusinessException was not thrown.');
        } catch (ApiBusinessException $e) {
            $this->assertSame(ApiCode::BAD_REQUEST, $e->apiCode);
            $this->assertSame('仅支持执行单条 CREATE TABLE 语句', $e->getMessage());
        }
    }
}
