<?php

namespace src\infrastructure\utils;

use Exception;
use PDO;

readonly class PDOSchemaAdapter implements DatabaseSchemaInterface
{
    public function __construct(
        private PDO    $pdo,
        private string $databaseType = 'sqlite'
    )
    {
    }

    public function getTableColumns(string $tableName): array
    {
        try {
            $stmt = match ($this->databaseType) {
                'mysql' => $this->pdo->prepare("SHOW COLUMNS FROM `$tableName`"),
                'postgresql' => $this->pdo->prepare("
                    SELECT column_name, data_type 
                    FROM information_schema.columns 
                    WHERE table_name = ?
                "),
                'sqlite' => $this->pdo->prepare("PRAGMA table_info($tableName)"),
                default => throw new Exception("Unsupported database type: $this->databaseType")
            };

            if ($this->databaseType === 'postgresql') {
                $stmt->execute([$tableName]);
            } else {
                $stmt->execute();
            }

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception) {
            return [];
        }
    }

    public function createColumn(string $tableName, string $columnName, string $type, array $options = []): bool
    {
        $sql = "ALTER TABLE `$tableName` ADD COLUMN `$columnName` $type";

        if (isset($options['default'])) {
            $sql .= " DEFAULT {$options['default']}";
        }

        return $this->pdo->exec($sql) !== false;
    }

    public function createTable(string $tableName, array $columns): bool
    {
        $columnDefs = [];
        foreach ($columns as $name => $def) {
            $columnDefs[] = "`$name` {$def['type']}" .
                (!$def['nullable'] ? ' NOT NULL' : '') .
                (isset($def['default']) ? " DEFAULT {$def['default']}" : '') .
                ($def['primary'] ?? false ? ' PRIMARY KEY' : '') .
                ($def['auto_increment'] ?? false ? ' AUTO_INCREMENT' : '');
        }

        $sql = "CREATE TABLE `$tableName` (" . implode(', ', $columnDefs) . ")";
        return $this->pdo->exec($sql) !== false;
    }

    public function tableExists(string $tableName): bool
    {
        try {
            $stmt = match ($this->databaseType) {
                'mysql' => $this->pdo->prepare("SHOW TABLES LIKE ?"),
                'postgresql' => $this->pdo->prepare("
                    SELECT table_name FROM information_schema.tables 
                    WHERE table_name = ? AND table_schema = 'public'
                "),
                'sqlite' => $this->pdo->prepare("
                    SELECT name FROM sqlite_master 
                    WHERE type='table' AND name = ?
                "),
                default => throw new Exception("Unsupported database type")
            };

            $stmt->execute([$tableName]);
            return $stmt->rowCount() > 0;
        } catch (Exception) {
            return false;
        }
    }
}