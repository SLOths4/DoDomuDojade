<?php
namespace src\utilities;

use PDO;

class ModuleService {
    private PDO $pdo;

    function __construct(PDO $pdo) {
        $this->pdo = $pdo;
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

    function toggleModule(string $moduleName, bool $status, PDO $pdo): void {
        $query = "UPDATE module_schedule SET is_active = :status WHERE module_name = :module_name";
        $statement = $pdo->prepare($query);
        $statement->execute([
            'status' => $status ? 1 : 0,
            'module_name' => $moduleName
        ]);
    }
}