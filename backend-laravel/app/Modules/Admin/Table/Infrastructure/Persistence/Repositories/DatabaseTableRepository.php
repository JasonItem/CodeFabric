<?php

declare(strict_types=1);

namespace App\Modules\Admin\Table\Infrastructure\Persistence\Repositories;

use App\Modules\Admin\Table\Domain\Contracts\TableRepositoryInterface;
use Illuminate\Support\Facades\DB;

final class DatabaseTableRepository implements TableRepositoryInterface
{
    public function listTables(string $keyword, int $page, int $pageSize): array
    {
        $offset = ($page - 1) * $pageSize;
        $like = '%' . $keyword . '%';

        $totalRows = DB::select(
            <<<'SQL'
            SELECT COUNT(*) AS total
            FROM information_schema.TABLES
            WHERE TABLE_SCHEMA = DATABASE()
              AND (? = '' OR TABLE_NAME LIKE ? OR TABLE_COMMENT LIKE ?)
            SQL,
            [$keyword, $like, $like]
        );

        $listRows = DB::select(
            <<<'SQL'
            SELECT
              TABLE_NAME AS tableName,
              TABLE_COMMENT AS tableComment,
              ENGINE AS engine,
              TABLE_ROWS AS tableRows,
              DATA_LENGTH AS dataLength,
              INDEX_LENGTH AS indexLength,
              CREATE_TIME AS createTime,
              UPDATE_TIME AS updateTime,
              TABLE_COLLATION AS collation
            FROM information_schema.TABLES
            WHERE TABLE_SCHEMA = DATABASE()
              AND (? = '' OR TABLE_NAME LIKE ? OR TABLE_COMMENT LIKE ?)
            ORDER BY TABLE_NAME ASC
            LIMIT ? OFFSET ?
            SQL,
            [$keyword, $like, $like, $pageSize, $offset]
        );

        $list = array_map(static function (object $item): array {
            return [
                'tableName' => (string) $item->tableName,
                'tableComment' => $item->tableComment,
                'engine' => $item->engine,
                'tableRows' => (int) ($item->tableRows ?? 0),
                'dataLength' => (int) ($item->dataLength ?? 0),
                'indexLength' => (int) ($item->indexLength ?? 0),
                'createTime' => $item->createTime,
                'updateTime' => $item->updateTime,
                'collation' => $item->collation,
            ];
        }, $listRows);

        return [
            'list' => $list,
            'total' => (int) (($totalRows[0]->total ?? 0)),
        ];
    }

    public function getColumns(string $tableName): array
    {
        $rows = DB::select(
            <<<'SQL'
            SELECT
              COLUMN_NAME AS columnName,
              COLUMN_TYPE AS columnType,
              DATA_TYPE AS dataType,
              IS_NULLABLE AS isNullable,
              COLUMN_DEFAULT AS columnDefault,
              COLUMN_COMMENT AS columnComment,
              COLUMN_KEY AS columnKey,
              EXTRA AS extra,
              CHARACTER_SET_NAME AS characterSetName,
              COLLATION_NAME AS collationName,
              ORDINAL_POSITION AS ordinalPosition
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?
            ORDER BY ORDINAL_POSITION ASC
            SQL,
            [$tableName]
        );

        return array_map(static function (object $row): array {
            return [
                'columnName' => (string) $row->columnName,
                'columnType' => (string) $row->columnType,
                'dataType' => (string) $row->dataType,
                'isNullable' => (string) $row->isNullable,
                'columnDefault' => $row->columnDefault,
                'columnComment' => $row->columnComment,
                'columnKey' => $row->columnKey,
                'extra' => $row->extra,
                'characterSetName' => $row->characterSetName,
                'collationName' => $row->collationName,
                'ordinalPosition' => (int) $row->ordinalPosition,
            ];
        }, $rows);
    }

    public function getCreateSql(string $tableName): string
    {
        $rows = DB::select("SHOW CREATE TABLE `{$tableName}`");
        $first = $rows[0] ?? null;
        if (!$first) {
            return '';
        }

        $values = (array) $first;
        foreach ($values as $value) {
            if (is_string($value) && str_starts_with(strtoupper($value), 'CREATE TABLE')) {
                return $value;
            }
        }

        return '';
    }

    public function getIndexes(string $tableName): array
    {
        $rows = DB::select(
            <<<'SQL'
            SELECT
              INDEX_NAME AS indexName,
              NON_UNIQUE AS nonUnique,
              INDEX_TYPE AS indexType,
              SEQ_IN_INDEX AS seqInIndex,
              COLUMN_NAME AS columnName,
              COLLATION AS collation,
              SUB_PART AS subPart,
              INDEX_COMMENT AS indexComment
            FROM information_schema.STATISTICS
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?
            ORDER BY INDEX_NAME ASC, SEQ_IN_INDEX ASC
            SQL,
            [$tableName]
        );

        $grouped = [];
        foreach ($rows as $row) {
            $name = (string) $row->indexName;
            if (!isset($grouped[$name])) {
                $grouped[$name] = [
                    'indexName' => $name,
                    'unique' => (int) $row->nonUnique === 0,
                    'indexType' => $row->indexType ?: 'BTREE',
                    'indexComment' => $row->indexComment ?: null,
                    'columns' => [],
                ];
            }

            $grouped[$name]['columns'][] = [
                'columnName' => (string) $row->columnName,
                'seqInIndex' => (int) $row->seqInIndex,
                'subPart' => $row->subPart === null ? null : (int) $row->subPart,
                'collation' => $row->collation,
            ];
        }

        return array_values($grouped);
    }

    public function getForeignKeys(string $tableName): array
    {
        $rows = DB::select(
            <<<'SQL'
            SELECT
              kcu.CONSTRAINT_NAME AS constraintName,
              kcu.COLUMN_NAME AS columnName,
              kcu.REFERENCED_TABLE_NAME AS referencedTableName,
              kcu.REFERENCED_COLUMN_NAME AS referencedColumnName,
              kcu.ORDINAL_POSITION AS ordinalPosition,
              rc.UPDATE_RULE AS updateRule,
              rc.DELETE_RULE AS deleteRule
            FROM information_schema.KEY_COLUMN_USAGE kcu
            JOIN information_schema.REFERENTIAL_CONSTRAINTS rc
              ON rc.CONSTRAINT_SCHEMA = kcu.CONSTRAINT_SCHEMA
              AND rc.CONSTRAINT_NAME = kcu.CONSTRAINT_NAME
              AND rc.TABLE_NAME = kcu.TABLE_NAME
            WHERE kcu.TABLE_SCHEMA = DATABASE()
              AND kcu.TABLE_NAME = ?
              AND kcu.REFERENCED_TABLE_NAME IS NOT NULL
            ORDER BY kcu.CONSTRAINT_NAME ASC, kcu.ORDINAL_POSITION ASC
            SQL,
            [$tableName]
        );

        $grouped = [];
        foreach ($rows as $row) {
            $name = (string) $row->constraintName;
            if (isset($grouped[$name])) {
                continue;
            }
            $grouped[$name] = [
                'constraintName' => $name,
                'columnName' => (string) $row->columnName,
                'referencedTableName' => (string) $row->referencedTableName,
                'referencedColumnName' => (string) $row->referencedColumnName,
                'updateRule' => $row->updateRule ?: 'RESTRICT',
                'deleteRule' => $row->deleteRule ?: 'RESTRICT',
            ];
        }

        return array_values($grouped);
    }

    public function getTableData(string $tableName): array
    {
        return array_map(static fn (object $row): array => (array) $row, DB::select("SELECT * FROM `{$tableName}`"));
    }

    public function getAllTableNames(): array
    {
        $rows = DB::select(
            <<<'SQL'
            SELECT TABLE_NAME AS tableName
            FROM information_schema.TABLES
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_TYPE = 'BASE TABLE'
            ORDER BY TABLE_NAME ASC
            SQL
        );

        return array_map(static fn (object $row): string => (string) $row->tableName, $rows);
    }

    public function tableExists(string $tableName): bool
    {
        $rows = DB::select(
            <<<'SQL'
            SELECT COUNT(*) AS count
            FROM information_schema.TABLES
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?
            SQL,
            [$tableName]
        );

        return ((int) ($rows[0]->count ?? 0)) > 0;
    }

    public function executeSql(string $sql): void
    {
        DB::statement($sql);
    }

    public function dropTable(string $tableName): void
    {
        DB::statement("DROP TABLE `{$tableName}`");
    }

    public function truncateTable(string $tableName): void
    {
        DB::statement("TRUNCATE TABLE `{$tableName}`");
    }
}

