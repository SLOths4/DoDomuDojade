<?php

namespace src\infrastructure\utils;

use ReflectionClass;
use ReflectionException;

class SchemaChecker
{
    private array $typeMapping = [
        'int' => ['type' => 'INT(11)', 'nullable' => true],
        '?int' => ['type' => 'INT(11)', 'nullable' => true],
        'string' => ['type' => 'VARCHAR(255)', 'nullable' => false],
        'DateTimeImmutable' => ['type' => 'DATETIME', 'nullable' => false],
        'bool' => ['type' => 'TINYINT(1)', 'nullable' => false],
        '?bool' => ['type' => 'TINYINT(1)', 'nullable' => true],
    ];

    public function __construct(
        private readonly DatabaseSchemaInterface $adapter,
        private readonly string $tableName
    ) {}

    /**
     * Sprawdza i tworzy brakujące kolumny na podstawie encji
     * @throws ReflectionException
     */
    public function ensureSchema(string $entityClass): bool
    {
        $reflection = new ReflectionClass($entityClass);
        $properties = $reflection->getProperties();

        $expectedColumns = $this->extractColumnsFromEntity($properties);

        // Sprawdź, czy tabela istnieje, jeśli nie - utwórz
        if (!$this->adapter->tableExists($this->tableName)) {
            return $this->adapter->createTable($this->tableName, $expectedColumns);
        }

        // Pobierz istniejące kolumny
        $existingColumns = $this->adapter->getTableColumns($this->tableName);
        $existingColumnNames = array_column($existingColumns, 'Field') ?:
            array_column($existingColumns, 'column_name') ?:
                array_column($existingColumns, 'name');

        // Dodaj brakujące kolumny
        foreach ($expectedColumns as $columnName => $definition) {
            if (!in_array($columnName, $existingColumnNames)) {
                $this->adapter->createColumn(
                    $this->tableName,
                    $columnName,
                    $definition['type'],
                    $definition
                );
            }
        }

        return true;
    }

    /**
     * Wydobywa definicje kolumn z właściwości encji
     */
    private function extractColumnsFromEntity(array $properties): array
    {
        $columns = [];

        foreach ($properties as $property) {
            $name = $property->getName();
            $type = $property->getType();

            if (!$type) {
                continue;
            }

            $typeName = $type->getName();
            $isNullable = $type->allowsNull();

            // Specjalne traktowanie dla id
            if ($name === 'id') {
                $columns[$name] = [
                    'type' => 'INT(11)',
                    'nullable' => true,
                    'primary' => true,
                    'auto_increment' => true
                ];
                continue;
            }

            // Mapowanie typów PHP na SQL
            $sqlType = $this->mapPhpTypeToSql($typeName, $isNullable);
            $columns[$name] = $sqlType;
        }

        return $columns;
    }

    /**
     * Mapuje typy PHP na typy SQL
     */
    private function mapPhpTypeToSql(string $phpType, bool $isNullable): array
    {
        $key = $isNullable ? "?$phpType" : $phpType;

        return $this->typeMapping[$key] ??
            $this->typeMapping[$phpType] ??
            ['type' => 'TEXT', 'nullable' => $isNullable];
    }

    /**
     * Pobiera dozwolone pola do edycji (pomija systemowe)
     * @throws ReflectionException
     */
    public function getAllowedFields(string $entityClass): array
    {
        $reflection = new ReflectionClass($entityClass);
        $properties = $reflection->getProperties();

        $systemFields = ['id', 'created_at', 'updated_at'];
        $allowedFields = [];

        foreach ($properties as $property) {
            $name = $property->getName();
            if (!in_array($name, $systemFields)) {
                $allowedFields[] = $name;
            }
        }

        return $allowedFields;
    }
}
