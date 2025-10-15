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
        private array $ALLOWED_FIELDS,
    ) {}

    /**
     * Creates a new module
     * @throws Exception
     */
    public function create(array $data): bool
    {
        $this->validate($data);
        $module = new Module(
            null,
            trim($data['moduleName']),
            (bool) $data['isActive'],
            new DateTimeImmutable($data['startTime']),
            new DateTimeImmutable($data['endTime'])
        );
        return $this->repo->add($module);
    }

    /**
     * Updates existing module
     * @throws Exception
     */
    public function update(int $id, array $data): bool
    {
        $this->validate($data);
        $module = $this->repo->findById($id);
        if ($module === null) {
            throw new Exception("Module not found with id: $id");
        }
        foreach ($this->ALLOWED_FIELDS as $field) {
            if (isset($data[$field])) {
                $value = $data[$field];
                if (in_array($field, ['start_t  ime', 'end_time'])) {
                    $module->$field = new DateTimeImmutable($value);
                } elseif ($field === 'is_active') {
                    $module->$field = (bool)$value;
                } else {
                    $module->$field = trim($value);
                }
            }
        }
        return $this->repo->update($module);
    }

    /**
     * Deletes a module
     * @throws Exception
     */
    public function delete(int $id): bool
    {
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
        $start = DateTimeImmutable::createFromFormat('H:i:s', $data['start_time']);
        $end = DateTimeImmutable::createFromFormat('H:i:s', $data['end_time']);
        if (!$start || !$end) {
            throw new Exception('Invalid time format, expected H:i:s');
        }
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
     * Gets all active modules
     * @throws Exception
     */
    public function getActive(): array
    {
        return $this->repo->findActive();
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
        $module = $this->repo->findById($id);
        if ($module === null) {
            throw new Exception("Module not found with id: $id");
        }

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
