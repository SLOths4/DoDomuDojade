<?php
namespace src\infrastructure\factories;

use PDO;
use Exception;
use src\config\config;

class PDOFactory
{
    /**
     * Tworzy i zwraca instancję PDO
     *
     * @return PDO
     * @throws Exception
     */
    public static function create(): PDO
    {
        try {
            $cfg = config::fromEnv();
            $dbDsn = $cfg->dbDsn();
            $dbUsername = $cfg->dbUsername();
            $dbPassword = $cfg->dbPassword();

            if (empty($dbDsn)) {
                throw new Exception('Database DSN cannot be empty.');
            }

            $pdo = (!empty($dbUsername) || !empty($dbPassword))
                ? new PDO($dbDsn, $dbUsername, $dbPassword)
                : new PDO($dbDsn);

            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            return $pdo;
        } catch (Exception $e) {
            throw new Exception("Błąd połączenia z bazą danych: " . $e->getMessage(), 0, $e);
        }
    }
}
