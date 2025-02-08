<?php

namespace src\utilities;

use Monolog\Logger;
use PDO;
use PDOException;
use PDOStatement;

class ModuleService
{
    private PDO $pdo;
    private Logger $logger;
    private string $moduleTableName;

    public function __construct(PDO $pdo, Logger $logger)
    {
        $this->pdo = $pdo;
        $this->logger = $logger;
        $this->moduleTableName = getenv('MODULE_TABLE_NAME') ?: 'modules';
        $this->logger->info('Usługa ModuleService została zainicjalizowana z nazwą tabeli: ' . $this->moduleTableName);
    }

    /**
     * Metoda pomocnicza do wykonywania zapytań.
     *
     * @param string $query
     * @param array $params
     * @return PDOStatement
     * @throws PDOException
     */
    private function executeQuery(string $query, array $params = []): PDOStatement
    {
        try {
            $statement = $this->pdo->prepare($query);
            $statement->execute($params);
            return $statement;
        } catch (PDOException $e) {
            $this->logger->error('Błąd podczas wykonywania zapytania', [
                'zapytanie' => $query,
                'parametry' => $params,
                'komunikat_błędu' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Sprawdza, czy moduł jest widoczny na podstawie czasu i statusu aktywności.
     *
     * @param string $moduleName
     * @return bool
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

        $this->logger->debug('Sprawdzanie widoczności modułu', [
            'nazwa_modułu' => $moduleName,
            'bieżący_czas' => $currentTime,
        ]);

        $statement = $this->executeQuery($query, [
            'module_name' => $moduleName,
            'current_time' => $currentTime,
        ]);

        $isVisible = $statement->fetchColumn() !== false;

        $this->logger->info('Widoczność modułu została sprawdzona', [
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
     */
    public function isModuleActive(string $moduleName): bool
    {
        $query = "SELECT 1 FROM $this->moduleTableName 
                  WHERE module_name = :module_name 
                  AND is_active = 1
                  LIMIT 1";

        $this->logger->debug('Sprawdzanie czy moduł jest aktywny', [
            'nazwa_modułu' => $moduleName,
        ]);

        $statement = $this->executeQuery($query, [
            'module_name' => $moduleName,
        ]);

        $isActive = $statement->fetchColumn() !== false;

        $this->logger->info('Status aktywności modułu został sprawdzony', [
            'nazwa_modułu' => $moduleName,
            'jest_aktywny' => $isActive,
        ]);

        return $isActive;
    }

    /**
     * Pobiera wszystkie moduły z tabeli.
     *
     * @return array
     */
    public function getModules(): array
    {
        $query = "SELECT * FROM $this->moduleTableName";

        $this->logger->debug('Pobieranie wszystkich modułów');

        $statement = $this->executeQuery($query);
        $modules = $statement->fetchAll(PDO::FETCH_ASSOC);

        $this->logger->info('Wszystkie moduły zostały pobrane', [
            'liczba_modułów' => count($modules),
        ]);

        return $modules;
    }

    /**
     * Pobiera dane pojedynczego modułu na podstawie jego nazwy.
     *
     * @param string $moduleName
     * @return array
     */
    public function getModule(string $moduleName): array
    {
        $query = "SELECT * FROM $this->moduleTableName WHERE module_name = :module_name LIMIT 1";

        $this->logger->debug('Pobieranie szczegółów pojedynczego modułu', [
            'nazwa_modułu' => $moduleName,
        ]);

        $statement = $this->executeQuery($query, [
            'module_name' => $moduleName,
        ]);

        $module = $statement->fetch(PDO::FETCH_ASSOC) ?: [];

        $this->logger->info('Moduł został pobrany', [
            'nazwa_modułu' => $moduleName,
            'wynik' => $module,
        ]);

        return $module;
    }

    /**
     * Pobiera wszystkie aktywne moduły.
     *
     * @return array
     */
    public function getActiveModules(): array
    {
        $query = "SELECT * FROM $this->moduleTableName WHERE is_active = 1";

        $this->logger->debug('Pobieranie aktywnych modułów');

        $statement = $this->executeQuery($query);
        $activeModules = $statement->fetchAll(PDO::FETCH_ASSOC);

        $this->logger->info('Aktywne moduły zostały pobrane', [
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
     */
    public function toggleModule(string $moduleName, bool $status): void
    {
        $query = "UPDATE $this->moduleTableName SET is_active = :status WHERE module_name = :module_name";

        $this->logger->debug('Zmiana statusu modułu', [
            'nazwa_modułu' => $moduleName,
            'nowy_status' => $status,
        ]);

        $this->executeQuery($query, [
            'status' => $status ? 1 : 0,
            'module_name' => $moduleName,
        ]);

        $this->logger->info('Status modułu został zmieniony', [
            'nazwa_modułu' => $moduleName,
            'nowy_status' => $status,
        ]);
    }
}