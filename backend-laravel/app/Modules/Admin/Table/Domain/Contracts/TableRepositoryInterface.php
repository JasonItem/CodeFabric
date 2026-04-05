<?php

declare(strict_types=1);

namespace App\Modules\Admin\Table\Domain\Contracts;

interface TableRepositoryInterface
{
    public function listTables(string $keyword, int $page, int $pageSize): array;

    public function getColumns(string $tableName): array;

    public function getCreateSql(string $tableName): string;

    public function getIndexes(string $tableName): array;

    public function getForeignKeys(string $tableName): array;

    public function getTableData(string $tableName): array;

    public function getAllTableNames(): array;

    public function tableExists(string $tableName): bool;

    public function executeSql(string $sql): void;

    public function dropTable(string $tableName): void;

    public function truncateTable(string $tableName): void;
}

