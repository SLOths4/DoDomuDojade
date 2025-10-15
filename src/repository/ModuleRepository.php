<?php

namespace src\repository;

use DateTimeImmutable;
use PDO;
use Psr\Log\LoggerInterface;
use Exception;
use src\core\Model;
use src\entities\Module;

class ModuleRepository extends Model
{
    public function __construct(
        PDO $pdo,
        LoggerInterface $logger,
        private readonly string $TABLE_NAME,
    ) {
        parent::__construct($pdo, $logger);
    }

    /**
     * Adds a new module
     * @throws Exception
     */
    public function add(Module $module): bool
    {
        $query = "INSERT INTO $this->TABLE_NAME (module_name, is_active, start_time, end_time) 
                  VALUES (:module_name, :is_active, :start_time, :end_time)";
        $params = [
            'module_name' => [$module->moduleName, PDO::PARAM_STR],
            'is_active' => [$module->isActive ? 1 : 0, PDO::PARAM_INT],
            'start_time' => [$module->startTime->format('H:i:s'), PDO::PARAM_STR],
            'end_time' => [$module->endTime->format('H:i:s'), PDO::PARAM_STR],
        ];
        $this->executeStatement($query, $params);
        return true;
    }

    /**
     * Finds module by ID and returns Module entity or null
     * @throws Exception
     */
    public function findById(int $id): ?Module
    {
        $query = "SELECT * FROM $this->TABLE_NAME WHERE id = :id LIMIT 1";
        $params = ['id' => [$id, PDO::PARAM_INT]];
        $rows = $this->executeStatement($query, $params);
        $row = $rows[0];
        return new Module(
            $row['id'],
            $row['module_name'],
            (bool) $row['is_active'],
            new DateTimeImmutable($row['start_time']),
            new DateTimeImmutable($row['end_time'])
        );
    }

    /**
     * Updates a module entity
     * @param Module $module
     * @return bool
     * @throws Exception
     */
    public function update(Module $module): bool
    {
        $this->logger->debug("Updating module", ["module" => $module]);

        $query = "
        UPDATE $this->TABLE_NAME 
        SET module_name = :module_name,
            is_active = :is_active,
            start_time = :start_time,
            end_time = :end_time
        WHERE id = :id
    ";

        $stmt = $this->pdo->prepare($query);

        $this->bindParams($stmt, [
            ':id' => [$module->id, PDO::PARAM_INT],
            ':module_name' => [$module->moduleName, PDO::PARAM_STR],
            ':is_active' => [$module->isActive ? 1 : 0, PDO::PARAM_INT],
            ':start_time' => [$module->startTime->format('H:i:s'), PDO::PARAM_STR],
            ':end_time' => [$module->endTime->format('H:i:s'), PDO::PARAM_STR],
        ]);

        $success = $stmt->execute();

        $this->logger->info("Module update " . ($success ? "successful" : "failed"));

        return $success && $stmt->rowCount() > 0;
    }

    /**
     * Deletes a module by ID
     * @throws Exception
     */
    public function delete(int $id): bool
    {
        $query = "DELETE FROM $this->TABLE_NAME WHERE id = :id";
        $params = ['id' => [$id, PDO::PARAM_INT]];
        $this->executeStatement($query, $params);
        return true;
    }

    /**
     * Finds all modules
     * @throws Exception
     */
    public function findAll(): array
    {
        $query = "SELECT * FROM $this->TABLE_NAME";
        $rows = $this->executeStatement($query);
        $modules = [];
        foreach ($rows as $row) {
            $modules[] = new Module(
                $row['id'],
                $row['module_name'],
                (bool)$row['is_active'],
                new DateTimeImmutable($row['start_time']),
                new DateTimeImmutable($row['end_time'])
            );
        }
        return $modules;
    }

    /**
     * Finds all active modules
     * @throws Exception
     */
    public function findActive(): array
    {
        $query = "SELECT * FROM $this->TABLE_NAME WHERE is_active = 1";
        $rows = $this->executeStatement($query);
        $modules = [];
        foreach ($rows as $row) {
            $modules[] = new Module(
                $row['id'],
                $row['module_name'],
                (bool)$row['is_active'],
                new DateTimeImmutable($row['start_time']),
                new DateTimeImmutable($row['end_time'])
            );
        }
        return $modules;
    }

    /**
     * @throws Exception
     */
    public function findByName(string $moduleName): ?Module
    {
        $query = "SELECT * FROM {$this->TABLE_NAME} WHERE module_name = :module_name LIMIT 1";
        $rows = $this->executeStatement($query, [
            ':module_name' => [$moduleName, \PDO::PARAM_STR]
        ]);
        $row = $rows[0];

        return new Module(
            $row['id'],
            $row['module_name'],
            (bool)$row['is_active'],
            new DateTimeImmutable($row['start_time']),
            new DateTimeImmutable($row['end_time']),
        );
    }

}
