<?php

declare(strict_types=1);

namespace App\Modules\Admin\Table\Application\Services;

use App\Enums\ApiCode;
use App\Modules\Admin\Table\Domain\Contracts\TableRepositoryInterface;
use App\Shared\Exceptions\ApiBusinessException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Throwable;

final class TableApplicationService
{
    private const IDENTIFIER_REG = '/^[A-Za-z_][A-Za-z0-9_]*$/';

    public function __construct(
        private readonly TableRepositoryInterface $tableRepository,
    ) {
    }

    public function list(string $keyword, int $page, int $pageSize): array
    {
        return $this->tableRepository->listTables($keyword, $page, $pageSize);
    }

    public function columns(string $tableName): array
    {
        $name = $this->assertTableName($tableName);
        if (!$this->tableRepository->tableExists($name)) {
            throw new ApiBusinessException(ApiCode::NOT_FOUND, '数据表不存在');
        }

        return $this->tableRepository->getColumns($name);
    }

    public function createSql(string $tableName): array
    {
        $name = $this->assertTableName($tableName);
        $createSql = $this->tableRepository->getCreateSql($name);
        if ($createSql === '') {
            throw new ApiBusinessException(ApiCode::BAD_REQUEST, '未获取到建表 SQL，请确认该对象是否为数据表');
        }

        return [
            'tableName' => $name,
            'createSql' => $createSql,
        ];
    }

    public function indexes(string $tableName): array
    {
        $name = $this->assertTableName($tableName);
        return $this->tableRepository->getIndexes($name);
    }

    public function foreignKeys(string $tableName): array
    {
        $name = $this->assertTableName($tableName);
        return $this->tableRepository->getForeignKeys($name);
    }

    public function export(string $tableName): array
    {
        $name = $this->assertTableName($tableName);
        $createSql = $this->tableRepository->getCreateSql($name);
        if ($createSql === '') {
            throw new ApiBusinessException(ApiCode::BAD_REQUEST, '未获取到建表 SQL，请确认该对象是否为数据表');
        }

        $rows = $this->tableRepository->getTableData($name);
        $statements = [];
        $statements[] = '-- CodeFabric 表导出: ' . $name;
        $statements[] = 'SET NAMES utf8mb4;';
        $statements[] = 'SET FOREIGN_KEY_CHECKS = 0;';
        $statements[] = $createSql . ';';

        if (!empty($rows)) {
            $allColumns = array_keys($rows[0]);
            $columnsSql = implode(', ', array_map(static fn ($col): string => "`{$col}`", $allColumns));
            foreach ($rows as $row) {
                $valuesSql = implode(', ', array_map(fn ($col): string => $this->serializeSqlValue($row[$col] ?? null), $allColumns));
                $statements[] = "INSERT INTO `{$name}` ({$columnsSql}) VALUES ({$valuesSql});";
            }
        }

        $statements[] = 'SET FOREIGN_KEY_CHECKS = 1;';

        return [
            'tableName' => $name,
            'fileName' => "{$name}.sql",
            'sql' => implode("\n", $statements),
        ];
    }

    public function exportAll(): array
    {
        $tableNames = $this->tableRepository->getAllTableNames();
        if (empty($tableNames)) {
            return [
                'fileName' => 'all-tables.sql',
                'sql' => '-- 当前数据库暂无可导出数据表',
                'tableCount' => 0,
            ];
        }

        $statements = [];
        $statements[] = '-- CodeFabric 全库导出，共 ' . count($tableNames) . ' 张表';
        $statements[] = 'SET NAMES utf8mb4;';
        $statements[] = 'SET FOREIGN_KEY_CHECKS = 0;';

        foreach ($tableNames as $tableName) {
            $createSql = $this->tableRepository->getCreateSql($tableName);
            if ($createSql === '') {
                continue;
            }
            $rows = $this->tableRepository->getTableData($tableName);
            $statements[] = '';
            $statements[] = '-- Table: ' . $tableName;
            $statements[] = $createSql . ';';

            if (!empty($rows)) {
                $allColumns = array_keys($rows[0]);
                $columnsSql = implode(', ', array_map(static fn ($col): string => "`{$col}`", $allColumns));
                foreach ($rows as $row) {
                    $valuesSql = implode(', ', array_map(fn ($col): string => $this->serializeSqlValue($row[$col] ?? null), $allColumns));
                    $statements[] = "INSERT INTO `{$tableName}` ({$columnsSql}) VALUES ({$valuesSql});";
                }
            }
        }

        $statements[] = 'SET FOREIGN_KEY_CHECKS = 1;';

        return [
            'fileName' => 'all-tables-' . now()->format('Ymd-His') . '.sql',
            'sql' => implode("\n", $statements),
            'tableCount' => count($tableNames),
        ];
    }

    public function importSqlFile(UploadedFile $file, string $mode): array
    {
        $fileName = (string) $file->getClientOriginalName();
        if (!Str::endsWith(Str::lower($fileName), '.sql')) {
            throw new ApiBusinessException(ApiCode::BAD_REQUEST, '仅支持导入 .sql 文件');
        }

        $mode = $mode === 'strict' ? 'strict' : 'skip-create';
        $raw = (string) file_get_contents((string) $file->getRealPath());
        $sql = preg_replace('/^\xEF\xBB\xBF/u', '', $raw);
        $sql = trim((string) $sql);
        if ($sql === '') {
            throw new ApiBusinessException(ApiCode::BAD_REQUEST, 'SQL 文件内容不能为空');
        }

        $statements = array_values(array_filter(array_map(
            fn (string $statement): string => trim($this->stripLeadingComments($statement)),
            $this->splitSqlStatements($sql)
        )));
        if (empty($statements)) {
            throw new ApiBusinessException(ApiCode::BAD_REQUEST, '未解析到可执行 SQL');
        }

        $allowed = ['CREATE TABLE', 'ALTER TABLE', 'INSERT INTO', 'DROP TABLE', 'TRUNCATE TABLE', 'SET '];
        foreach ($statements as $statement) {
            $upper = strtoupper($statement);
            $ok = false;
            foreach ($allowed as $prefix) {
                if (str_starts_with($upper, $prefix)) {
                    $ok = true;
                    break;
                }
            }
            if (!$ok) {
                throw new ApiBusinessException(ApiCode::BAD_REQUEST, '导入 SQL 含不允许语句：' . mb_substr($statement, 0, 40) . '...');
            }
        }

        $existsCache = [];
        $skippedTables = [];
        $executedCount = 0;
        $skippedCount = 0;

        try {
            foreach ($statements as $statement) {
                $upper = strtoupper($statement);
                if ($mode === 'skip-create' && str_starts_with($upper, 'CREATE TABLE')) {
                    $createTableName = $this->parseCreateTableName($statement);
                    if ($createTableName !== '') {
                        if (!array_key_exists($createTableName, $existsCache)) {
                            $existsCache[$createTableName] = $this->tableRepository->tableExists($createTableName);
                        }
                        if ($existsCache[$createTableName] === true) {
                            $skippedCount++;
                            $skippedTables[$createTableName] = true;
                            continue;
                        }
                        $existsCache[$createTableName] = true;
                    }
                }

                $this->tableRepository->executeSql($statement);
                $executedCount++;
            }
        } catch (Throwable $e) {
            if (str_contains($e->getMessage(), 'already exists')) {
                throw new ApiBusinessException(ApiCode::CONFLICT, '数据表已存在');
            }
            throw $e;
        }

        return [
            'count' => $executedCount,
            'skippedCount' => $skippedCount,
            'skippedTables' => array_keys($skippedTables),
            'mode' => $mode,
            'fileName' => $fileName,
        ];
    }

    public function createBySql(string $sql): bool
    {
        $normalized = $this->normalizeSql($sql);
        if ($normalized === '') {
            throw new ApiBusinessException(ApiCode::BAD_REQUEST, '仅支持执行单条 CREATE TABLE 语句');
        }
        if (!str_starts_with(strtoupper($normalized), 'CREATE TABLE')) {
            throw new ApiBusinessException(ApiCode::BAD_REQUEST, '只允许 CREATE TABLE 语句');
        }

        $this->tableRepository->executeSql($normalized);

        return true;
    }

    public function alterBySql(string $sql): bool
    {
        $normalized = $this->normalizeSql($sql);
        if ($normalized === '') {
            throw new ApiBusinessException(ApiCode::BAD_REQUEST, '仅支持执行单条 ALTER TABLE 语句');
        }
        if (!str_starts_with(strtoupper($normalized), 'ALTER TABLE')) {
            throw new ApiBusinessException(ApiCode::BAD_REQUEST, '只允许 ALTER TABLE 语句');
        }

        $this->tableRepository->executeSql($normalized);

        return true;
    }

    public function remove(string $tableName): bool
    {
        $name = $this->assertTableName($tableName);
        $this->tableRepository->dropTable($name);
        return true;
    }

    public function truncate(string $tableName): bool
    {
        $name = $this->assertTableName($tableName);
        $this->tableRepository->truncateTable($name);
        return true;
    }

    private function assertTableName(string $tableName): string
    {
        $name = trim($tableName);
        if (!preg_match(self::IDENTIFIER_REG, $name)) {
            throw new ApiBusinessException(ApiCode::BAD_REQUEST, '无效的数据表名称');
        }
        return $name;
    }

    private function normalizeSql(string $sql): string
    {
        $cleaned = trim($this->stripLeadingComments($sql));
        if ($cleaned === '') {
            return '';
        }

        $withoutTrailingSemicolon = rtrim($cleaned, ';');
        if (str_contains($withoutTrailingSemicolon, ';')) {
            return '';
        }

        return $cleaned;
    }

    private function stripLeadingComments(string $sql): string
    {
        $rest = ltrim($sql);

        while (str_starts_with($rest, '--') || str_starts_with($rest, '/*')) {
            if (str_starts_with($rest, '--')) {
                $idx = strpos($rest, "\n");
                if ($idx === false) {
                    return '';
                }
                $rest = ltrim(substr($rest, $idx + 1));
                continue;
            }

            $idx = strpos($rest, '*/');
            if ($idx === false) {
                return '';
            }
            $rest = ltrim(substr($rest, $idx + 2));
        }

        return $rest;
    }

    private function splitSqlStatements(string $rawSql): array
    {
        $statements = [];
        $current = '';
        $quote = null;
        $escaped = false;

        $len = strlen($rawSql);
        for ($i = 0; $i < $len; $i++) {
            $char = $rawSql[$i];

            if ($quote !== null) {
                $current .= $char;
                if ($escaped) {
                    $escaped = false;
                    continue;
                }
                if ($char === '\\') {
                    $escaped = true;
                    continue;
                }
                if ($char === $quote) {
                    $quote = null;
                }
                continue;
            }

            if (in_array($char, ["'", '"', '`'], true)) {
                $quote = $char;
                $current .= $char;
                continue;
            }

            if ($char === ';') {
                $trimmed = trim($current);
                if ($trimmed !== '') {
                    $statements[] = $trimmed;
                }
                $current = '';
                continue;
            }

            $current .= $char;
        }

        $tail = trim($current);
        if ($tail !== '') {
            $statements[] = $tail;
        }

        return $statements;
    }

    private function parseCreateTableName(string $sql): string
    {
        if (preg_match('/CREATE\s+TABLE\s+(?:IF\s+NOT\s+EXISTS\s+)?`?([A-Za-z_][A-Za-z0-9_]*)`?/i', $sql, $matches)) {
            return $matches[1];
        }

        return '';
    }

    private function serializeSqlValue(mixed $value): string
    {
        if ($value === null) {
            return 'NULL';
        }
        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }
        if (is_bool($value)) {
            return $value ? '1' : '0';
        }
        if ($value instanceof \DateTimeInterface) {
            return "'" . $value->format('Y-m-d H:i:s') . "'";
        }
        if (is_string($value)) {
            $escaped = str_replace(["\\", "'", "\0"], ["\\\\", "\\'", ''], $value);
            return "'" . $escaped . "'";
        }

        $str = (string) $value;
        $escaped = str_replace(["\\", "'", "\0"], ["\\\\", "\\'", ''], $str);
        return "'" . $escaped . "'";
    }
}
