<?php

namespace App\Infrastructure\Persistence;

use App\Domain\Module\Module;
use App\Domain\Module\ModuleException;
use App\Domain\Module\ModuleName;
use App\Domain\Module\ModuleRepositoryInterface;
use App\Infrastructure\Database\DatabaseService;
use DateTimeImmutable;
use PDO;

/**
 * @inheritDoc
 */
readonly class PDOModuleRepository implements ModuleRepositoryInterface
{
    private const string DEFAULT_START_TIME = '00:00:00';
    private const string DEFAULT_END_TIME = '23:59:59';

    public function __construct(
        private DatabaseService $dbHelper,
        private string          $TABLE_NAME,
        private string          $DATE_FORMAT
    ) {}

    /**
     * Maps array from database to entity
     * @param array $row
     * @return Module
     * @throws ModuleException
     */
    private function mapRow(array $row): Module
    {
        $startTime = DateTimeImmutable::createFromFormat($this->DATE_FORMAT, $row['start_time']) ?: DateTimeImmutable::createFromFormat($this->DATE_FORMAT, self::DEFAULT_START_TIME);
        $endTime   = DateTimeImmutable::createFromFormat($this->DATE_FORMAT, $row['end_time']) ?: DateTimeImmutable::createFromFormat($this->DATE_FORMAT, self::DEFAULT_END_TIME);

        return new Module(
            (int)$row['id'],
            ModuleName::fromString((string)$row['module_name']),
            (bool)$row['is_active'],
            $startTime,
            $endTime
        );
    }

    /**
     * @inheritDoc
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
     * @inheritDoc
     */
    public function update(Module $module): bool
    {
        $affected = $this->dbHelper->update(
            $this->TABLE_NAME,
            [
                'module_name' => [$module->moduleName->value, PDO::PARAM_STR],
                'is_active'   => [$module->isActive, PDO::PARAM_BOOL],
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
     * @inheritDoc
     */
    public function findAll(): array
    {
        $rows = $this->dbHelper->getAll("SELECT * FROM $this->TABLE_NAME");

        return array_map(fn(array $row) => $this->mapRow($row), $rows);
    }

    /**
     * @inheritDoc
     */
    public function findByName(ModuleName $moduleName): ?Module
    {
        $row = $this->dbHelper->getOne(
            "SELECT * FROM $this->TABLE_NAME WHERE module_name = :module_name LIMIT 1",
            [':module_name' => [$moduleName->value, PDO::PARAM_STR]]
        );

        if ($row === null) {
            return null;
        }

        return $this->mapRow($row);
    }
}