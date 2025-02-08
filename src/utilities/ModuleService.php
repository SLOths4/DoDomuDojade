<?php
namespace src\utilities;

use PDO;

class ModuleService {
    private PDO $pdo;
    private string $moduleTableName;

    function __construct(PDO $pdo) {
        $this->pdo = $pdo;

        $this->moduleTableName = getenv('MODULE_TABLE_NAME') ?? 'modules';
    }
    function isModuleVisible(string $moduleName, PDO $pdo): bool {
        $currentTime = date('H:i:s');

        $query = "SELECT 1 FROM module_schedule 
              WHERE module_name = :module_name 
              AND is_active = 1 
              AND start_time <= :current_time 
              AND end_time >= :current_time
              LIMIT 1";

        $statement = $pdo->prepare($query);
        $statement->execute([
            'module_name' => $moduleName,
            'current_time' => $currentTime
        ]);

        return $statement->fetchColumn() !== false;
    }

    function isModuleActive(string $moduleName, PDO $pdo): bool {
        $query = "SELECT 1 FROM module_schedule 
              WHERE module_name = :module_name 
              AND is_active = 1
              LIMIT 1";
        $statement = $pdo->prepare($query);
        $statement->execute([
            'module_name' => $moduleName
        ]);

        return $statement->fetchColumn() !== false;
    }

    function getModules()
    {
        $query = "SELECT * FROM $this->moduleTableName";
        $statement = $this->pdo->prepare($query);
        $statement->execute();
        return $statement->fetchAll();
    }

    function toggleModule(string $moduleName, bool $status, PDO $pdo): void {
        $query = "UPDATE $this->moduleTableName SET is_active = :status WHERE module_name = :module_name";
        $statement = $pdo->prepare($query);
        $statement->execute([
            'status' => $status ? 1 : 0,
            'module_name' => $moduleName
        ]);
    }
}