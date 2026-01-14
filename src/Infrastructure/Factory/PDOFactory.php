<?php
declare(strict_types=1);

namespace App\Infrastructure\Factory;

use App\config\Config;
use App\Domain\Exception\DatabaseException;
use App\Domain\Shared\FactoryInterface;
use Exception;
use PDO;

/**
 * PDO factory
 */
final class PDOFactory
{
    /**
     * Creates PDO instance
     * @param string $dbDsn
     * @param string $dbUsername
     * @param string $dbPassword
     * @return PDO
     * @throws DatabaseException
     */
    public static function create(string $dbDsn, string $dbUsername = "", string $dbPassword = ""): PDO
    {
        try {
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
