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
     * Sprawdza, czy moduł jest widoczny na podstawie czasu i statusu aktywności.
     *
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

        self::$logger->debug('Sprawdzanie widoczności modułu', [
            'nazwa_modułu' => $moduleName,
            'bieżący_czas' => $currentTime,
        ]);

        $statement = $this->executeStatement($query, [
            'module_name' => [$moduleName, PDO::PARAM_STR],
            'current_time' => [$currentTime, PDO::PARAM_STR],
        ]);

        $isVisible = !empty($statement);

        self::$logger->info('Widoczność modułu została sprawdzona', [
            'nazwa_modułu' => $moduleName,
            'jest_widoczny' => $isVisible,
        ]);

        return $isVisible;
    }

    /**
     * Sprawdza, czy moduł jest aktywny.
     *
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

        self::$logger->debug('Sprawdzanie czy moduł jest aktywny', [
            'nazwa_modułu' => $moduleName,
        ]);

        $statement = $this->executeStatement($query, [
            'module_name' => [$moduleName, PDO::PARAM_STR],
        ]);

        $isActive = !empty($statement);

        self::$logger->info('Status aktywności modułu został sprawdzony', [
            'nazwa_modułu' => $moduleName,
            'jest_aktywny' => $isActive,
        ]);

        return $isActive;
    }

    /**
     * Pobiera wszystkie moduły z tabeli.
     *
     * @return array
     * @throws Exception
     */
    public function getModules(): array
    {
        $query = "SELECT * FROM $this->moduleTableName";

        self::$logger->debug('Pobieranie wszystkich modułów');

        $modules = $this->executeStatement($query);

        self::$logger->info('Wszystkie moduły zostały pobrane', [
            'liczba_modułów' => count($modules),
        ]);

        return $modules;
    }

    /**
     * Pobiera dane pojedynczego modułu na podstawie jego nazwy.
     *
     * @param string $moduleName
     * @return array
     * @throws Exception
     */
    public function getModule(string $moduleName): array
    {
        $query = "SELECT * FROM $this->moduleTableName WHERE module_name = :module_name LIMIT 1";

        self::$logger->debug('Pobieranie szczegółów pojedynczego modułu', [
            'nazwa_modułu' => $moduleName,
        ]);

        $statement = $this->executeStatement($query, [
            'module_name' => [$moduleName, PDO::PARAM_STR],
        ]);

        $module = $statement[0] ?: [];

        self::$logger->info('Moduł został pobrany', [
            'nazwa_modułu' => $moduleName,
            'wynik' => $module,
        ]);

        return $module;
    }

    /**
     * Pobiera wszystkie aktywne moduły.
     *
     * @return array
     * @throws Exception
     */
    public function getActiveModules(): array
    {
        $query = "SELECT * FROM $this->moduleTableName WHERE is_active = 1";

        self::$logger->debug('Pobieranie aktywnych modułów');

        $activeModules = $this->executeStatement($query);

        self::$logger->info('Aktywne moduły zostały pobrane', [
            'liczba_aktywnych_modułów' => count($activeModules),
        ]);

        return $activeModules;
    }

    /**
     * Zmienia status aktywności modułu.
     *
     * @param string $moduleName
     * @param bool $status
     * @return void
     * @throws Exception
     */
    public function toggleModule(string $moduleName, bool $status): void
    {
        $query = "UPDATE $this->moduleTableName SET is_active = :status WHERE module_name = :module_name";

        self::$logger->debug('Zmiana statusu modułu', [
            'nazwa_modułu' => $moduleName,
            'nowy_status' => $status,
        ]);

        $this->executeStatement($query, [
            'status' => [$status ? 1 : 0, PDO::PARAM_INT],
            'module_name' => [$moduleName, PDO::PARAM_STR],
        ]);

        self::$logger->info('Status modułu został zmieniony', [
            'nazwa_modułu' => $moduleName,
            'nowy_status' => $status,
        ]);
    }
}