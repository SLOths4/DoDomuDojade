<?php

namespace src\models;

use Exception;
use PDO;
use src\core\Model;

class ModuleModel extends Model
{
    // DB structure: id | module_name | is_active (0, 1) | start_time | end_time
    private string $moduleTableName;

    public function __construct()
    {
        $this->moduleTableName = self::getConfigVariable('MODULE_TABLE_NAME') ?: 'modules';
        self::$logger->info('Modules table being used: ' . $this->moduleTableName);
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
        $query = "SELECT 1 FROM $this->moduleTableName 
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
     * Checks if module is active.
     * @param string $moduleName
     * @return bool
     * @throws Exception
     */
    public function isModuleActive(string $moduleName): bool
    {
        $query = "SELECT 1 FROM $this->moduleTableName 
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
        $query = "SELECT * FROM $this->moduleTableName";

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
     * Fetches module details by name.
     * @param string $moduleName
     * @return array
     * @throws Exception
     */
    public function getModuleByName(string $moduleName): array
    {
        $query = "SELECT * FROM $this->moduleTableName WHERE module_name = :module_name LIMIT 1";

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
        $query = "SELECT * FROM $this->moduleTableName WHERE is_active = 1";
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
        $query = "UPDATE $this->moduleTableName SET is_active = :status WHERE module_name = :module_name";

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
}