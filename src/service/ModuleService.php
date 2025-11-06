<?php

namespace src\service;

use src\repository\ModuleRepository;
use src\entities\Module;
use Exception;
use DateTimeImmutable;

readonly class ModuleService
{
    public function __construct(
        private ModuleRepository $repo,
        private string $DATE_FORMAT,
    ) {}

    /**
     * Checks if a module exists by id
     * @param int $id
     * @throws Exception
     */
    private function moduleExists(int $id): void
    {
        $module = $this->repo->findById($id);
        if ($module === null) {
            throw new Exception("Module with id: $id does not exist");
        }
    }

    /**
     * Updates existing module
     * @throws Exception
     */
    public function update(int $id, array $data): bool
    {
        $this->validate($data);
        $this->moduleExists($id);

        $Module = $this->repo->findById($id);

        $startTime = isset($data['start_time']) 
            ? DateTimeImmutable::createFromFormat($this->DATE_FORMAT, $data['start_time'])
            : $Module->startTime;
            
        $endTime = isset($data['end_time'])
            ? DateTimeImmutable::createFromFormat($this->DATE_FORMAT, $data['end_time'])
            : $Module->endTime;

        $updatedModule = new Module(
            $id,
            $Module->moduleName,
            isset($data['is_active']) ? (bool)$data['is_active'] : $Module->isActive,
            $startTime,
            $endTime
        );

        return $this->repo->update($updatedModule);
    }

    /**
     * Deletes a module
     * @throws Exception
     */
    public function delete(int $id): bool
    {
        $this->moduleExists($id);
        return $this->repo->delete($id);
    }

    /**
     * Validates input data
     * @throws Exception
     */
    private function validate(array $data): void
    {
        if (empty($data['module_name'])) {
            throw new Exception('Module name is required');
        }
        if (!isset($data['is_active'])) {
            throw new Exception('Module active status is required');
        }
        if (empty($data['start_time']) || empty($data['end_time'])) {
            throw new Exception('Module start and end time are required');
        }
        
        $start = DateTimeImmutable::createFromFormat($this->DATE_FORMAT, $data['start_time']);
        $end = DateTimeImmutable::createFromFormat($this->DATE_FORMAT, $data['end_time']);
        
        if ($end <= $start) {
            throw new Exception('End time must be greater than start time');
        }
    }

    /**
     * Gets all modules
     * @throws Exception
     */
    public function getAll(): array
    {
        return $this->repo->findAll();
    }

    /**
     * Gets module by id
     * @throws Exception
     */
    public function getById(int $id): ?Module
    {
        return $this->repo->findById($id);
    }

    /**
     * Checks if the given module is visible (active and within start and end time)
     * @param string $moduleName
     * @return bool
     * @throws Exception
     */
    public function isVisible(string $moduleName): bool
    {
        $now = new DateTimeImmutable();
        $module = $this->repo->findByName($moduleName);
        
        if ($module === null) {
            throw new Exception("Module not found with name: $moduleName");
        }
        
        if (!$module->isActive) {
            return false;
        }
        
        return ($module->startTime <= $now && $module->endTime >= $now);
    }

    /**
     * Toggles the active status of a module by its ID
     * @param int $id
     * @return bool
     * @throws Exception
     */
    public function toggle(int $id): bool
    {
        $this->moduleExists($id);
        $module = $this->repo->findById($id);

        $toggledModule = new Module(
            $module->id,
            $module->moduleName,
            !$module->isActive,
            $module->startTime,
            $module->endTime
        );

        return $this->repo->update($toggledModule);
    }
}
