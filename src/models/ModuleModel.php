<?php

namespace src\models;

use Exception;
use InvalidArgumentException;
use PDO;
use RuntimeException;
use src\core\Model;

class ModuleModel extends Model
{
    // DB structure: id | module_name | is_active (0, 1) | start_time | end_time
    private array $ALLOWED_FIELDS;
    private string $TABLE_NAME;

    public function __construct()
    {
        $this->TABLE_NAME = self::getConfigVariable('MODULE_TABLE_NAME') ?: 'modules';
        $this->ALLOWED_FIELDS = self::getConfigVariable("ALLOWED_FIELDS") ?? ['module_name', 'is_active', 'start_time', 'end_time'];
        self::$logger->info('Modules table being used: ' . $this->TABLE_NAME);
    }

    /**
     * Checks if a module is visible based on current time and status.
     * @param string $moduleName
     * @return bool
     * @throws Exception
     */
    public function isModuleVisible(string $moduleName): bool
    {
        $currentTime = date('H:i:s');
        $query = "SELECT 1 FROM $this->TABLE_NAME 
                  WHERE module_name = :module_name 
                  AND is_active = 1 
                  AND start_time <= :current_time 
                  AND end_time >= :current_time
                  LIMIT 1";

        self::$logger->debug('Checking visibility of module:', [
            'module_name' => $moduleName,
            'current_time' => $currentTime,
        ]);

        try {
            $statement = $this->executeStatement($query, [
                'module_name' => [$moduleName, PDO::PARAM_STR],
                'current_time' => [$currentTime, PDO::PARAM_STR],
            ]);

            $isVisible = !empty($statement);

            self::$logger->info('Visibility of module was checked.', [
                'module_namee' => $moduleName,
                'is_visible' => $isVisible,
            ]);

            return $isVisible;
        } catch (Exception $e) {
            throw new Exception('Error checking visibility of module: ' . $e->getMessage());
        }
    }

    /**
     * Checks if a module is active.
     * @param string $moduleName
     * @return bool
     * @throws Exception
     */
    public function isModuleActive(string $moduleName): bool
    {
        $query = "SELECT 1 FROM $this->TABLE_NAME 
                  WHERE module_name = :module_name 
                  AND is_active = 1
                  LIMIT 1";

        self::$logger->debug('Checking status of model:', [
            'module_name' => $moduleName,
        ]);

        try {
            $statement = $this->executeStatement($query, [
                'module_name' => [$moduleName, PDO::PARAM_STR],
            ]);

            $isActive = !empty($statement);

            self::$logger->info('Status of model was checked.', [
                'module_name' => $moduleName,
                'is_active' => $isActive,
            ]);

            return $isActive;
        } catch (Exception $e) {
            throw new Exception('Error checking status of model: ' . $e->getMessage());
        }
    }

    /**
     * Fetches all modules.
     * @return array
     * @throws Exception
     */
    public function getModules(): array
    {
        $query = "SELECT * FROM $this->TABLE_NAME";

        self::$logger->debug('Fetching all modules.');

        try {
            $modules = $this->executeStatement($query);

            self::$logger->info('All modules fetched successfully.', [
                'number_of_modules' => count($modules),
            ]);

            return $modules;
        } catch (Exception $e) {
            throw new Exception('Error fetching modules: ' . $e->getMessage());
        }

    }

    /**
     * Fetches module details by id.
     * @param int $moduleId
     * @return array
     * @throws Exception
     */
    public function getModuleById(int $moduleId): array
    {
        $query = "SELECT * FROM $this->TABLE_NAME WHERE id = :module_id LIMIT 1";

        self::$logger->debug('Fetching module details by name:', [
            'module_id' => $moduleId,
        ]);

        try {
            $statement = $this->executeStatement($query, [
                'module_id' => [$moduleId, PDO::PARAM_INT],
            ]);

            $module = $statement[0] ?: [];

            self::$logger->info('Module details fetched successfully.', [
                'module_id' => $moduleId,
                'details' => $module,
            ]);

            return $module;
        } catch (Exception $e) {
            throw new Exception('Error fetching module details: ' . $e->getMessage());
        }
    }

    /**
     * Fetches module details by name.
     * @param string $moduleName
     * @return array
     * @throws Exception
     */
    public function getModuleByName(string $moduleName): array
    {
        $query = "SELECT * FROM $this->TABLE_NAME WHERE module_name = :module_name LIMIT 1";

        self::$logger->debug('Fetching module details by name:', [
            'module_name' => $moduleName,
        ]);

        try {
            $statement = $this->executeStatement($query, [
                'module_name' => [$moduleName, PDO::PARAM_STR],
            ]);

            $module = $statement[0] ?: [];

            self::$logger->info('Module details fetched successfully.', [
                'module_name' => $moduleName,
                'details' => $module,
            ]);

            return $module;
        } catch (Exception $e) {
            throw new Exception('Error fetching module details: ' . $e->getMessage());
        }
    }

    /**
     * Fetches all available modules.
     * @return array
     * @throws Exception
     */
    public function getActiveModules(): array
    {
        $query = "SELECT * FROM $this->TABLE_NAME WHERE is_active = 1";
        self::$logger->debug('Fetching active modules.');

        try {
            $activeModules = $this->executeStatement($query);

            self::$logger->info('Active modules fetched successfully.', [
                'number_of_active_modules' => count($activeModules),
            ]);

            return $activeModules;
        } catch (Exception $e) {
            throw new Exception('Error fetching active modules: ' . $e->getMessage());
        }
    }

    /**
     * Changes module status.
     * @param string $moduleName
     * @param bool $status
     * @return void
     * @throws Exception
     */
    public function toggleModule(string $moduleName, bool $status): void
    {
        $query = "UPDATE $this->TABLE_NAME SET is_active = :status WHERE module_name = :module_name";

        self::$logger->debug('Changing module status', [
            'module_name' => $moduleName,
            'module_status' => $status,
        ]);

        try {
            $this->executeStatement($query, [
                'status' => [$status ? 1 : 0, PDO::PARAM_INT],
                'module_name' => [$moduleName, PDO::PARAM_STR],
            ]);

            self::$logger->info('Module status changed successfully.', [
                'module_name' => $moduleName,
                'status' => $status,
            ]);
        } catch (Exception $e) {
            throw new Exception('Error changing module status: ' . $e->getMessage());
        }
    }

    /**
     * Updated chosen filed of an announcement with a given value
     * @param int $moduleId
     * @param string $field
     * @param string $newValue
     * @return bool
     * @throws Exception
     */
    public function updateModuleField(int $moduleId, string $field, string $newValue): bool {
        if (!in_array($field, $this->ALLOWED_FIELDS, true)) {
            self::$logger->warning("Invalid field attempted for update.", [
                'field' => $field
            ]);
            throw new InvalidArgumentException("Invalid field to update: $field");
        }

        try {
            $query = "UPDATE $this->TABLE_NAME SET $field = :value WHERE id = :moduleId";
            $params = [
                ':value' => [$newValue, PDO::PARAM_STR],
                ':moduleId' => [$moduleId, PDO::PARAM_INT],
            ];

            $this->executeStatement($query, $params);
            self::$logger->info("Module updated.", [
                'moduleId' => $moduleId,
                'field' => $field,
                'newValue' => $newValue
            ]);
            return true;
        } catch (Exception $e) {
            throw new RuntimeException('Error updating module' . $e->getMessage());
        }
    }
}