<?php

namespace App\Application\UseCase;

use DateTimeImmutable;
use Exception;
use Psr\Log\LoggerInterface;
use App\Domain\Module;
use App\Infrastructure\Repository\ModuleRepository;

readonly class ModuleService
{
    public function __construct(
        private ModuleRepository $repo,
        private string           $DATE_FORMAT,
        private LoggerInterface  $logger,
    ) {}

    /**
     * Checks if a module exists by id
     * @param int $id
     * @throws Exception
     */
    private function moduleExists(int $id): void
    {
        $this->logger->debug('Checking if module exists', [
            'module_id' => $id,
        ]);

        $module = $this->repo->findById($id);
        if ($module === null) {
            $this->logger->warning('Module does not exist', [
                'module_id' => $id,
            ]);
            throw new Exception("Module with id: $id does not exist");
        }

        $this->logger->debug('Module exists', [
            'module_id' => $id,
        ]);
    }

    /**
     * Updates existing module
     * @param int $id
     * @param array $data
     * @return bool
     * @throws Exception
     */
    public function update(int $id, array $data): bool
    {
        $this->logger->info('Updating module', [
            'module_id' => $id,
            'payload_keys' => array_keys($data),
        ]);

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

        $result = $this->repo->update($updatedModule);

        $this->logger->info('Module update finished', [
            'module_id' => $id,
            'success' => $result,
        ]);

        return $result;
    }

    /**
     * Validates input data
     * @param array $data
     * @return void
     * @throws Exception
     */
    private function validate(array $data): void
    {
        $this->logger->debug('Validating module data', [
            'has_module_name' => array_key_exists('module_name', $data),
            'has_is_active' => array_key_exists('is_active', $data),
            'has_start_time' => !empty($data['start_time'] ?? null),
            'has_end_time' => !empty($data['end_time'] ?? null),
        ]);

        if (empty($data['module_name'])) {
            $this->logger->warning('Missing module name');
            throw new Exception('Module name is required');
        }
        if (!isset($data['is_active'])) {
            $this->logger->warning('Missing module active status');
            throw new Exception('Module active status is required');
        }
        if (empty($data['start_time']) || empty($data['end_time'])) {
            $this->logger->warning('Missing module start or end time');
            throw new Exception('Module start and end time are required');
        }

        $start = DateTimeImmutable::createFromFormat($this->DATE_FORMAT, $data['start_time']);
        $end = DateTimeImmutable::createFromFormat($this->DATE_FORMAT, $data['end_time']);

        if ($end <= $start) {
            $this->logger->warning('Invalid module time range', [
                'start_time' => $data['start_time'],
                'end_time' => $data['end_time'],
            ]);
            throw new Exception('End time must be greater than start time');
        }

        $this->logger->debug('Module data validation passed');
    }

    /**
     * Gets all modules
     * @return array
     * @throws Exception
     */
    public function getAll(): array
    {
        $this->logger->debug('Fetching all modules');

        $modules = $this->repo->findAll();

        $this->logger->debug('Fetched all modules', [
            'count' => count($modules),
        ]);

        return $modules;
    }

    /**
     * Gets module by id
     * @param int $id
     * @return ?Module
     * @throws Exception
     */
    public function getById(int $id): ?Module
    {
        $this->logger->debug('Fetching module by id', [
            'module_id' => $id,
        ]);

        $module = $this->repo->findById($id);

        $this->logger->debug('Fetched module by id', [
            'module_id' => $id,
            'found' => $module !== null,
        ]);

        return $module;
    }

    /**
     * Checks if the given module is visible (active and within start and end time)
     * @param string $moduleName
     * @return bool
     * @throws Exception
     */
    public function isVisible(string $moduleName): bool
    {
        $this->logger->debug('Checking if module is visible', [
            'module_name' => $moduleName,
        ]);

        $now = new DateTimeImmutable();
        $module = $this->repo->findByName($moduleName);

        if ($module === null) {
            $this->logger->warning('Module not found when checking visibility', [
                'module_name' => $moduleName,
            ]);
            throw new Exception("Module not found with name: $moduleName");
        }

        if (!$module->isActive) {
            $this->logger->debug('Module is not active', [
                'module_name' => $moduleName,
            ]);
            return false;
        }

        $visible = ($module->startTime <= $now && $module->endTime >= $now);

        $this->logger->debug('Module visibility evaluated', [
            'module_name' => $moduleName,
            'visible' => $visible,
        ]);

        return $visible;
    }

    /**
     * Toggles the active status of a module by its ID
     * @param int $id
     * @return bool success
     * @throws Exception
     */
    public function toggle(int $id): bool
    {
        $this->logger->info('Toggling module active status', [
            'module_id' => $id,
        ]);

        $this->moduleExists($id);
        $module = $this->repo->findById($id);

        $toggledModule = new Module(
            $module->id,
            $module->moduleName,
            !$module->isActive,
            $module->startTime,
            $module->endTime
        );

        $result = $this->repo->update($toggledModule);

        $this->logger->info('Module toggle finished', [
            'module_id' => $id,
            'success' => $result,
            'new_status' => !$module->isActive,
        ]);

        return $result;
    }
}