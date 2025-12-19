<?php
declare(strict_types=1);

namespace App\Infrastructure\Factory;

use Exception;
use PDO;
use App\config\Config;
use App\Infrastructure\Exception\DatabaseException;

final class PDOFactory
{
    /**
     * Creates PDO instance
     * @return PDO
     * @throws Exception
     */
    public static function create(): PDO
    {
        try {
            $cfg = Config::fromEnv();
            $dbDsn = $cfg->dbDsn();
            $dbUsername = $cfg->dbUsername();
            $dbPassword = $cfg->dbPassword();

            if (empty($dbDsn)) {
                throw DatabaseException::invalidCredentials($dbDsn);
            }

            $pdo = (!empty($dbUsername) || !empty($dbPassword))
                ? new PDO($dbDsn, $dbUsername, $dbPassword)
                : new PDO($dbDsn);

            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            return $pdo;
        } catch (Exception $e) {
            throw DatabaseException::connectionFailed($dbDsn, $e);
        }
    }
}
