<?php

namespace src\infrastructure\utils;

interface DatabaseSchemaInterface
{
    public function getTableColumns(string $tableName): array;

    public function createColumn(string $tableName, string $columnName, string $type, array $options = []): bool;

    public function createTable(string $tableName, array $columns): bool;

    public function tableExists(string $tableName): bool;
}