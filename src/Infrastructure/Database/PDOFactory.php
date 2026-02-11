<?php
declare(strict_types=1);

namespace App\Infrastructure\Database;

use PDO;
use Throwable;

/**
 * Creates PDO instance
 */
final class PDOFactory
{
    public static function create(
        string $dbDsn,
        string $dbUsername = "",
        string $dbPassword = ""
    ): PDO {
        try {
            if (empty($dbDsn)) {
                throw DatabaseException::invalidDsn($dbDsn);
            }

            $pdo = (!empty($dbUsername) || !empty($dbPassword))
                ? new PDO($dbDsn, $dbUsername, $dbPassword)
                : new PDO($dbDsn);

            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            return $pdo;
        } catch (Throwable $e) {
            throw DatabaseException::connectionFailed($dbDsn, $e);
        }
    }
}
