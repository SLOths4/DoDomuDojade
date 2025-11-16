<?php

namespace src\repository;

use DateTimeImmutable;
use PDO;
use Psr\Log\LoggerInterface;
use Exception;
use src\entities\Module;
use src\infrastructure\helpers\DatabaseHelper;

readonly class ModuleRepository
{
    public function __construct(
        private PDO             $pdo,
        private LoggerInterface $logger,
        private DatabaseHelper $dbHelper,
        private string  $TABLE_NAME,
        private string  $DATE_FORMAT,
    ) {}

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
            'start_time' => [$module->startTime->format($this->DATE_FORMAT), PDO::PARAM_STR],
            'end_time' => [$module->endTime->format($this->DATE_FORMAT), PDO::PARAM_STR],
        ];
        $this->dbHelper->executeStatement($query, $params);
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
        $stmt = $this->dbHelper->executeStatement($query, $params);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$row) {
            return null;
        }

        $startTime = DateTimeImmutable::createFromFormat($this->DATE_FORMAT, $row['start_time']);
        $endTime = DateTimeImmutable::createFromFormat($this->DATE_FORMAT, $row['end_time']);

        return new Module(
            $row['id'],
            $row['module_name'],
            (bool) $row['is_active'],
            $startTime,
            $endTime
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
        $this->logger->debug("Updating module", ["module_id" => $module->id]);

        $query = "
            UPDATE $this->TABLE_NAME 
            SET module_name = :module_name,
                is_active = :is_active,
                start_time = :start_time,
                end_time = :end_time
            WHERE id = :id
        ";

        $stmt = $this->pdo->prepare($query);

        $this->dbHelper->bindParams($stmt, [
            ':id' => [$module->id, PDO::PARAM_INT],
            ':module_name' => [$module->moduleName, PDO::PARAM_STR],
            ':is_active' => [$module->isActive ? 1 : 0, PDO::PARAM_INT],
            ':start_time' => [$module->startTime->format($this->DATE_FORMAT), PDO::PARAM_STR],
            ':end_time' => [$module->endTime->format($this->DATE_FORMAT), PDO::PARAM_STR],
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
        $this->dbHelper->executeStatement($query, $params);
        return true;
    }

    /**
     * Finds all modules
     * @throws Exception
     */
    public function findAll(): array
    {
        $query = "SELECT * FROM $this->TABLE_NAME";
        $stmt = $this->dbHelper->executeStatement($query);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $modules = [];
        
        foreach ($rows as $row) {
            try {
                $startTime = DateTimeImmutable::createFromFormat($this->DATE_FORMAT, $row['start_time']);
                $endTime = DateTimeImmutable::createFromFormat($this->DATE_FORMAT, $row['end_time']);

                $modules[] = new Module(
                    $row['id'],
                    $row['module_name'],
                    (bool)$row['is_active'],
                    $startTime,
                    $endTime
                );
            } catch (Exception $e) {
                $this->logger->error("Failed to parse module record", [
                    'module_id' => $row['id'] ?? 'unknown',
                    'error' => $e->getMessage()
                ]);
                throw $e;
            }
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
        $stmt = $this->dbHelper->executeStatement($query);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $modules = [];
        
        foreach ($rows as $row) {
            try {
                $startTime = DateTimeImmutable::createFromFormat($this->DATE_FORMAT, $row['start_time']);
                $endTime = DateTimeImmutable::createFromFormat($this->DATE_FORMAT, $row['end_time']);

                $modules[] = new Module(
                    $row['id'],
                    $row['module_name'],
                    (bool)$row['is_active'],
                    $startTime,
                    $endTime
                );
            } catch (Exception $e) {
                $this->logger->error("Failed to parse active module record", [
                    'module_id' => $row['id'] ?? 'unknown',
                    'error' => $e->getMessage()
                ]);
                throw $e;
            }
        }
        
        return $modules;
    }

    /**
     * @throws Exception
     */
    public function findByName(string $moduleName): ?Module
    {
        $query = "SELECT * FROM $this->TABLE_NAME WHERE module_name = :module_name LIMIT 1";
        $stmt = $this->dbHelper->executeStatement($query, [
            ':module_name' => [$moduleName, PDO::PARAM_STR]
        ]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$row) {
            return null;
        }

        $startTime = DateTimeImmutable::createFromFormat($this->DATE_FORMAT, $row['start_time']);
        $endTime = DateTimeImmutable::createFromFormat($this->DATE_FORMAT, $row['end_time']);

        return new Module(
            $row['id'],
            $row['module_name'],
            (bool)$row['is_active'],
            $startTime,
            $endTime,
        );
    }
}