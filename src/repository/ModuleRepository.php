<?php

namespace src\repository;

use DateTimeImmutable;
use Exception;
use PDO;
use src\entities\Module;
use src\infrastructure\helpers\DatabaseHelper;

readonly class ModuleRepository
{
    public function __construct(
        private DatabaseHelper $dbHelper,
        private string         $TABLE_NAME,
        private string         $DATE_FORMAT,
    ) {}

    /**
     * @throws Exception
     */
    private function mapRow(array $row): Module
    {
        $startTime = DateTimeImmutable::createFromFormat($this->DATE_FORMAT, $row['start_time']);
        $endTime   = DateTimeImmutable::createFromFormat($this->DATE_FORMAT, $row['end_time']);

        if (!$startTime || !$endTime) {
            throw new Exception('Nieprawidłowy format daty w rekordzie modułu');
        }

        return new Module(
            (int)$row['id'],
            (string)$row['module_name'],
            (bool)$row['is_active'],
            $startTime,
            $endTime
        );
    }

    /**
     * Finds module by ID and returns Module entity or null
     * @throws Exception
     */
    public function findById(int $id): ?Module
    {
        $row = $this->dbHelper->getOne(
            "SELECT * FROM $this->TABLE_NAME WHERE id = :id LIMIT 1",
            [':id' => [$id, PDO::PARAM_INT]]
        );

        if ($row === null) {
            return null;
        }

        return $this->mapRow($row);
    }

    /**
     * Updates a module entity
     * @param Module $module
     * @return bool
     * @throws Exception
     */
    public function update(Module $module): bool
    {
        $affected = $this->dbHelper->update(
            $this->TABLE_NAME,
            [
                'module_name' => [$module->moduleName, PDO::PARAM_STR],
                'is_active'   => [$module->isActive ? 1 : 0, PDO::PARAM_INT],
                'start_time'  => [$module->startTime->format($this->DATE_FORMAT), PDO::PARAM_STR],
                'end_time'    => [$module->endTime->format($this->DATE_FORMAT), PDO::PARAM_STR],
            ],
            [
                'id' => [$module->id, PDO::PARAM_INT],
            ]
        );

        return $affected > 0;
    }

    /**
     * Finds all modules
     * @throws Exception
     */
    public function findAll(): array
    {
        $rows = $this->dbHelper->getAll("SELECT * FROM $this->TABLE_NAME");

        return array_map(fn(array $row) => $this->mapRow($row), $rows);
    }

    /**
     * Finds all active modules
     * @throws Exception
     */
    public function findActive(): array
    {
        $rows = $this->dbHelper->getAll(
            "SELECT * FROM $this->TABLE_NAME WHERE is_active = 1"
        );

        return array_map(fn(array $row) => $this->mapRow($row), $rows);
    }

    /**
     * @throws Exception
     */
    public function findByName(string $moduleName): ?Module
    {
        $row = $this->dbHelper->getOne(
            "SELECT * FROM $this->TABLE_NAME WHERE module_name = :module_name LIMIT 1",
            [':module_name' => [$moduleName, PDO::PARAM_STR]]
        );

        if ($row === null) {
            return null;
        }

        return $this->mapRow($row);
    }
}