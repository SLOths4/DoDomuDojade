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
     * @param Config $cfg
     * @return PDO
     * @throws Exception
     */
    public function create(Config $cfg): PDO
    {
        $dbDsn = $cfg->dbDsn();
        try {
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
